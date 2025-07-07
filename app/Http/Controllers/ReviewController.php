<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReviewController extends Controller
{
    /**
     * Afficher le formulaire de création d'avis
     */
    public function create(Request $request, Reservation $reservation)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur peut laisser un avis pour cette réservation
        if ($reservation->parent_id !== $user->id && $reservation->babysitter_id !== $user->id) {
            abort(403, 'Vous ne pouvez pas laisser d\'avis pour cette réservation.');
        }
        
        // Vérifier que le service est terminé
        if (!in_array($reservation->status, ['completed', 'service_completed'])) {
            abort(403, 'Vous ne pouvez laisser un avis que pour un service terminé.');
        }
        
        // Vérifier que l'utilisateur n'a pas déjà laissé d'avis
        $userRole = $reservation->parent_id === $user->id ? 'parent' : 'babysitter';
        $hasReviewed = $userRole === 'parent' ? $reservation->parent_reviewed : $reservation->babysitter_reviewed;
        
        if ($hasReviewed) {
            return redirect()->route('messaging.index')->with('error', 'Vous avez déjà laissé un avis pour cette réservation.');
        }
        
        // Déterminer qui est évalué
        $reviewedUser = $userRole === 'parent' ? $reservation->babysitter : $reservation->parent;
        
        return Inertia::render('Reviews/Create', [
            'reservation' => [
                'id' => $reservation->id,
                'service_start_at' => $reservation->service_start_at,
                'service_end_at' => $reservation->service_end_at,
                'ad_title' => $reservation->application->ad->title ?? 'Service de babysitting',
            ],
            'reviewed_user' => [
                'id' => $reviewedUser->id,
                'firstname' => $reviewedUser->firstname,
                'lastname' => $reviewedUser->lastname,
                'avatar' => $reviewedUser->avatar,
            ],
            'user_role' => $userRole
        ]);
    }
    
    /**
     * Enregistrer un nouvel avis
     */
    public function store(Request $request, Reservation $reservation)
    {
        $user = Auth::user();
        
        // Vérifications de sécurité
        if ($reservation->parent_id !== $user->id && $reservation->babysitter_id !== $user->id) {
            abort(403);
        }
        
        if (!in_array($reservation->status, ['completed', 'service_completed'])) {
            abort(403, 'Le service doit être terminé pour laisser un avis.');
        }
        
        $userRole = $reservation->parent_id === $user->id ? 'parent' : 'babysitter';
        $hasReviewed = $userRole === 'parent' ? $reservation->parent_reviewed : $reservation->babysitter_reviewed;
        
        if ($hasReviewed) {
            return back()->with('error', 'Vous avez déjà laissé un avis pour cette réservation.');
        }
        
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Déterminer qui est évalué
            $reviewedUserId = $userRole === 'parent' ? $reservation->babysitter_id : $reservation->parent_id;
            
            // Créer l'avis
            Review::create([
                'reviewer_id' => $user->id,
                'reviewed_id' => $reviewedUserId,
                'reservation_id' => $reservation->id,
                'role' => $userRole,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);
            
            // Marquer comme évalué dans la réservation
            $updateField = $userRole === 'parent' ? 'parent_reviewed' : 'babysitter_reviewed';
            $reservation->update([$updateField => true]);
            
            DB::commit();
            
            Log::info('Avis créé avec succès', [
                'reservation_id' => $reservation->id,
                'reviewer_id' => $user->id,
                'reviewed_id' => $reviewedUserId,
                'rating' => $validated['rating']
            ]);
            
            return redirect()->route('messaging.index')->with('success', 'Votre avis a été publié avec succès !');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de l\'avis', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'user_id' => $user->id
            ]);
            
            return back()->with('error', 'Une erreur est survenue lors de la publication de votre avis.');
        }
    }
    
    /**
     * Afficher les avis d'un utilisateur
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $reviews = Review::with(['reviewer', 'reviewed', 'reservation.application.ad'])
            ->where('reviewed_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return Inertia::render('Reviews/Index', [
            'reviews' => $reviews,
            'average_rating' => $user->averageRating(),
            'total_reviews' => $user->reviews()->count()
        ]);
    }
}
