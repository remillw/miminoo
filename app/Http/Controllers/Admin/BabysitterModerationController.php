<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\BabysitterProfileVerified;
use App\Notifications\BabysitterProfileRejected;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BabysitterModerationController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function index()
    {
        $pendingBabysitters = User::with(['babysitterProfile', 'address'])
            ->whereHas('roles', function($query) {
                $query->where('name', 'babysitter');
            })
            ->whereHas('babysitterProfile', function($query) {
                $query->where('verification_status', 'pending');
            })
            ->get();

        return Inertia::render('Admin/BabysitterModeration', [
            'pendingBabysitters' => $pendingBabysitters
        ]);
    }

    public function verify(Request $request, User $babysitter)
    {
        $request->validate([
            'status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string'
        ]);

        $profile = $babysitter->babysitterProfile;
        
        if ($request->status === 'verified') {
            $profile->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
                'rejection_reason' => null
            ]);

            // Envoyer la notification de vérification
            $babysitter->notify(new BabysitterProfileVerified());

            // Créer automatiquement le compte Stripe Connect
            try {
                if (!$babysitter->stripe_account_id) {
                    Log::info('💳 Création du compte Stripe Connect pour babysitter vérifié', [
                        'babysitter_id' => $babysitter->id,
                        'babysitter_name' => $babysitter->firstname . ' ' . $babysitter->lastname
                    ]);

                    $this->stripeService->createConnectAccount($babysitter);
                    
                    Log::info('✅ Compte Stripe Connect créé avec succès', [
                        'babysitter_id' => $babysitter->id,
                        'stripe_account_created' => true
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Erreur lors de la création du compte Stripe Connect', [
                    'babysitter_id' => $babysitter->id,
                    'error' => $e->getMessage()
                ]);
                
                // On ne fait pas échouer la vérification si Stripe échoue
                // Le babysitter pourra configurer Stripe plus tard
            }
        } else {
            $profile->update([
                'verification_status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'verified_at' => null,
                'verified_by' => null
            ]);

            // Envoyer la notification de rejet
            $babysitter->notify(new BabysitterProfileRejected($request->rejection_reason));
        }

        return back()->with('success', 'Statut du profil mis à jour avec succès.');
    }
} 