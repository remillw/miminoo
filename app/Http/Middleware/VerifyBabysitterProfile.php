<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBabysitterProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('babysitter')) {
            $profile = $user->babysitterProfile;

            // Si le profil n'est pas vérifié, on redirige vers la page de profil
            if (!$profile || $profile->verification_status !== 'verified') {
                if ($request->route()->getName() !== 'babysitter.profile') {
                    return redirect()->route('babysitter.profile')
                        ->with('warning', 'Votre profil doit être vérifié avant de pouvoir postuler aux annonces.');
                }
            }
        }

        return $next($request);
    }
} 