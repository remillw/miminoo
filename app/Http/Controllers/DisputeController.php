<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\DisputeCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DisputeController extends Controller
{
    /**
     * Afficher le formulaire de création de réclamation
     */
    public function create(Reservation $reservation)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut faire une réclamation pour cette réservation
        if ($reservation->parent_id !== $user->id && $reservation->babysitter_id !== $user->id) {
            abort(403, 'Vous ne pouvez pas faire de réclamation pour cette réservation.');
        }
        
        // Vérifier qu'il n'y a pas déjà une réclamation en cours
        $existingDispute = Dispute::where('reservation_id', $reservation->id)
            ->where('reporter_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();
            
        if ($existingDispute) {
            return redirect()->route('disputes.show', $existingDispute)
                ->with('info', 'Vous avez déjà une réclamation en cours pour cette réservation.');
        }
        
        // Déterminer contre qui la réclamation est faite
        $reportedUser = $reservation->parent_id === $user->id ? $reservation->babysitter : $reservation->parent;
        
        return Inertia::render('Disputes/Create', [
            'reservation' => [
                'id' => $reservation->id,
                'service_start_at' => $reservation->service_start_at,
                'service_end_at' => $reservation->service_end_at,
                'ad_title' => $reservation->application->ad->title ?? 'Service de babysitting',
                'status' => $reservation->status,
            ],
            'reported_user' => [
                'id' => $reportedUser->id,
                'firstname' => $reportedUser->firstname,
                'lastname' => $reportedUser->lastname,
                'avatar' => $reportedUser->avatar,
            ],
            'reasons' => [
                'service_not_provided' => 'Service non fourni',
                'poor_service_quality' => 'Qualité de service insuffisante',
                'late_arrival' => 'Retard important',
                'early_departure' => 'Départ anticipé',
                'inappropriate_behavior' => 'Comportement inapproprié',
                'payment_issue' => 'Problème de paiement',
                'other' => 'Autre'
            ]
        ]);
    }
    
    /**
     * Enregistrer une nouvelle réclamation
     */
    public function store(Request $request, Reservation $reservation)
    {
        $user = Auth::user();
        
        // Vérifications de sécurité
        if ($reservation->parent_id !== $user->id && $reservation->babysitter_id !== $user->id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'reason' => 'required|in:service_not_provided,poor_service_quality,late_arrival,early_departure,inappropriate_behavior,payment_issue,other',
            'description' => 'required|string|min:10|max:2000',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Déterminer contre qui la réclamation est faite
            $reportedUserId = $reservation->parent_id === $user->id ? $reservation->babysitter_id : $reservation->parent_id;
            
            // Créer la réclamation
            $dispute = Dispute::create([
                'reservation_id' => $reservation->id,
                'reporter_id' => $user->id,
                'reported_id' => $reportedUserId,
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'status' => 'pending'
            ]);
            
            // Notifier l'utilisateur qui a créé la réclamation
            $user->notify(new DisputeCreatedNotification($dispute, false));
            
            // Notifier les administrateurs
            $admins = User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new DisputeCreatedNotification($dispute, true));
            }
            
            DB::commit();
            
            Log::info('Réclamation créée avec succès', [
                'dispute_id' => $dispute->id,
                'reservation_id' => $reservation->id,
                'reporter_id' => $user->id,
                'reported_id' => $reportedUserId,
                'reason' => $validated['reason']
            ]);
            
            return redirect()->route('disputes.show', $dispute)
                ->with('success', 'Votre réclamation a été créée avec succès. Notre équipe va l\'examiner rapidement.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la réclamation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'user_id' => $user->id
            ]);
            
            return back()->with('error', 'Une erreur est survenue lors de la création de votre réclamation.');
        }
    }
    
    /**
     * Afficher une réclamation
     */
    public function show(Dispute $dispute)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut voir cette réclamation
        if ($dispute->reporter_id !== $user->id && $dispute->reported_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }
        
        $dispute->load(['reporter', 'reported', 'reservation.application.ad', 'resolvedBy']);
        
        return Inertia::render('Disputes/Show', [
            'dispute' => $dispute,
            'can_respond' => $user->hasRole('admin') && $dispute->status !== 'resolved'
        ]);
    }
    
    /**
     * Lister les réclamations de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();
        
        $disputes = Dispute::with(['reported', 'reservation.application.ad'])
            ->where('reporter_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return Inertia::render('Disputes/Index', [
            'disputes' => $disputes
        ]);
    }
}
