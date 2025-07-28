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

            Log::info('✅ Babysitter vérifié - pas de création automatique du compte Stripe Connect', [
                'babysitter_id' => $babysitter->id,
                'babysitter_name' => $babysitter->firstname . ' ' . $babysitter->lastname,
                'note' => 'Le babysitter devra configurer son compte Stripe via l\'onboarding dédié'
            ]);
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