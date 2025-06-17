<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckBabysitterVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredAction = 'payments'): Response
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est authentifié et a le rôle babysitter
        if (!$user || !$user->hasRole('babysitter')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $babysitterProfile = $user->babysitterProfile;

        // Vérifier que le profil existe
        if (!$babysitterProfile) {
            return redirect()->route('dashboard')->with('error', 'Profil babysitter introuvable.');
        }

        // Vérifier le statut de vérification selon l'action demandée
        switch ($requiredAction) {
            case 'payments':
                // Pour accéder aux paiements, le profil doit être vérifié
                if ($babysitterProfile->verification_status !== 'verified') {
                    return redirect()->route('dashboard')->with('error', 'Votre profil doit être vérifié pour accéder aux paiements. Veuillez compléter votre profil et attendre la vérification.');
                }
                break;
                
            case 'apply':
                // Pour postuler, le profil doit être vérifié
                if ($babysitterProfile->verification_status !== 'verified') {
                    return redirect()->route('announcements.index')->with('error', 'Votre profil doit être vérifié pour postuler aux annonces. Veuillez compléter votre profil et attendre la vérification.');
                }
                break;
                
            case 'verified_only':
                // Accès général aux fonctionnalités réservées aux vérifiés
                if ($babysitterProfile->verification_status !== 'verified') {
                    return redirect()->route('dashboard')->with('warning', 'Cette fonctionnalité est réservée aux babysitters vérifiées.');
                }
                break;
        }

        return $next($request);
    }
} 