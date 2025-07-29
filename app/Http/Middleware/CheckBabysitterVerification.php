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
        Log::info('🔒 MIDDLEWARE VERIFICATION BABYSITTER', [
            'user_id' => $request->user()?->id,
            'required_action' => $requiredAction,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'expects_json' => $request->expectsJson()
        ]);

        $user = $request->user();

        // Vérifier que l'utilisateur est authentifié et a le rôle babysitter
        if (!$user || !$user->hasRole('babysitter')) {
            Log::warning('❌ MIDDLEWARE: Utilisateur non authentifié ou pas babysitter', [
                'user_exists' => $user ? true : false,
                'user_id' => $user?->id,
                'user_roles' => $user?->roles?->pluck('name'),
                'has_babysitter_role' => $user ? $user->hasRole('babysitter') : false
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Accès non autorisé.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $babysitterProfile = $user->babysitterProfile;

        Log::info('👤 MIDDLEWARE: Informations profil babysitter', [
            'user_id' => $user->id,
            'babysitter_profile_exists' => $babysitterProfile ? true : false,
            'verification_status' => $babysitterProfile?->verification_status,
            'required_action' => $requiredAction
        ]);

        // Vérifier que le profil existe
        if (!$babysitterProfile) {
            Log::warning('❌ MIDDLEWARE: Profil babysitter introuvable', [
                'user_id' => $user->id
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Profil babysitter introuvable.'], 404);
            }
            return redirect()->route('dashboard')->with('error', 'Profil babysitter introuvable.');
        }

        // Vérifier le statut de vérification selon l'action demandée
        switch ($requiredAction) {
            case 'payments':
                // Pour accéder aux paiements, le profil doit être vérifié
                if ($babysitterProfile->verification_status !== 'verified') {
                    Log::warning('❌ MIDDLEWARE: Profil non vérifié pour paiements', [
                        'user_id' => $user->id,
                        'verification_status' => $babysitterProfile->verification_status,
                        'expected' => 'verified',
                        'action' => 'payments'
                    ]);
                    
                    $errorMessage = 'Votre profil doit être vérifié pour accéder aux paiements. Veuillez compléter votre profil et attendre la vérification.';
                    if ($request->expectsJson()) {
                        return response()->json(['error' => $errorMessage], 403);
                    }
                    return redirect()->route('dashboard')
                        ->with('warning', '🔒 Accès restreint')
                        ->with('info', 'Vous devez être vérifié par notre équipe pour accéder à cette page.');
                }
                break;
                
            case 'apply':
                // Pour postuler, le profil doit être vérifié
                if ($babysitterProfile->verification_status !== 'verified') {
                    Log::warning('❌ MIDDLEWARE: Profil non vérifié pour postulation', [
                        'user_id' => $user->id,
                        'verification_status' => $babysitterProfile->verification_status,
                        'expected' => 'verified',
                        'action' => 'apply'
                    ]);
                    
                    $errorMessage = 'Votre profil doit être vérifié pour postuler aux annonces. Veuillez compléter votre profil et attendre la vérification.';
                    if ($request->expectsJson()) {
                        return response()->json(['error' => $errorMessage], 403);
                    }
                    return redirect()->route('announcements.index')->with('error', $errorMessage);
                }
                break;
                
            case 'verified_only':
                // Accès général aux fonctionnalités réservées aux vérifiés
                if ($babysitterProfile->verification_status !== 'verified') {
                    Log::warning('❌ MIDDLEWARE: Profil non vérifié pour fonctionnalité restreinte', [
                        'user_id' => $user->id,
                        'verification_status' => $babysitterProfile->verification_status,
                        'expected' => 'verified',
                        'action' => 'verified_only'
                    ]);
                    
                    $errorMessage = 'Cette fonctionnalité est réservée aux babysitters vérifiées.';
                    if ($request->expectsJson()) {
                        return response()->json(['error' => $errorMessage], 403);
                    }
                    return redirect()->route('dashboard')->with('warning', $errorMessage);
                }
                break;
        }

        Log::info('✅ MIDDLEWARE: Vérification réussie, passage au contrôleur', [
            'user_id' => $user->id,
            'verification_status' => $babysitterProfile->verification_status,
            'action' => $requiredAction
        ]);

        return $next($request);
    }
} 