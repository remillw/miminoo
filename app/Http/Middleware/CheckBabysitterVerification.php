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
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Recharger le profil depuis la base de données pour avoir le statut le plus récent
        $profile = $user->babysitterProfile()->first();

        Log::info('Middleware CheckBabysitterVerification appelé', [
            'user_id' => $user->id,
            'status' => $profile?->verification_status,
        ]);

        if (!$profile) {
            return response()->json([
                'message' => 'Profil babysitter non trouvé'
            ], 404);
        }

        if ($profile->verification_status === null || $profile->verification_status === '') {
            return $next($request);
        }

        if ($profile->verification_status === 'pending') {
            return response()->json([
                'message' => 'Une demande de vérification est déjà en cours'
            ], 400);
        }

        if ($profile->verification_status === 'verified') {
            return response()->json([
                'message' => 'Votre profil est déjà vérifié'
            ], 400);
        }

        return $next($request);
    }
} 