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

        // Le middleware ne vérifie plus le statut de vérification de profil,
        // seulement que l'utilisateur a un profil babysitter
        Log::info('✅ MIDDLEWARE: Profil babysitter trouvé, accès autorisé', [
            'user_id' => $user->id,
            'action' => $requiredAction
        ]);

        Log::info('✅ MIDDLEWARE: Vérification réussie, passage au contrôleur', [
            'user_id' => $user->id,
            'action' => $requiredAction
        ]);

        return $next($request);
    }
} 