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
        Log::info('🔒 MIDDLEWARE STRIPE VERIFICATION BABYSITTER', [
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

        // Vérifier la configuration Stripe Connect pour les actions qui le nécessitent
        if ($requiredAction === 'payments' && $user->stripe_account_status !== 'active') {
            Log::warning('❌ MIDDLEWARE: Compte Stripe non configuré', [
                'user_id' => $user->id,
                'stripe_account_status' => $user->stripe_account_status
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Configuration Stripe requise.',
                    'redirect_to' => '/babysitter/paiements'
                ], 403);
            }
            return redirect()->route('babysitter.payments')
                ->with('warning', 'Vous devez configurer votre compte Stripe pour accéder aux paiements.');
        }

        Log::info('✅ MIDDLEWARE: Vérifications réussies, accès autorisé', [
            'user_id' => $user->id,
            'action' => $requiredAction,
            'stripe_status' => $user->stripe_account_status
        ]);

        return $next($request);
    }
} 