<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Log tout ce que le mobile envoie AVANT l'authentification
        Log::info('=== LOGIN REQUEST - DONNÉES REÇUES ===', [
            'all_input_data' => $request->all(),
            'headers' => [
                'user_agent' => $request->header('User-Agent'),
                'x_mobile_app' => $request->header('X-Mobile-App'),
                'content_type' => $request->header('Content-Type'),
            ],
            'mobile_specific_data' => [
                'mobile_auth' => $request->input('mobile_auth'),
                'device_token' => $request->input('device_token'),
                'platform' => $request->input('platform'),
                'notification_provider' => $request->input('notification_provider'),
            ],
            'is_mobile_detected' => (
                $request->input('mobile_auth') === 'true' ||
                $request->header('X-Mobile-App') === 'true'
            ),
        ]);

        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // Associer les annonces guests existantes avec cet email
        $this->associateGuestAnnouncements($user);

        // Traitement simple pour mobile
        if ($request->input('mobile_auth') === 'true') {
            Log::info('=== MOBILE LOGIN DETECTED ===', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'device_token_received' => $request->input('device_token') ? 'YES' : 'NO',
                'device_token_preview' => $request->input('device_token') ? 
                    substr($request->input('device_token'), 0, 30) . '...' : 'NULL',
                'platform' => $request->input('platform'),
                'notification_provider' => $request->input('notification_provider'),
            ]);

            // Sauver le device token si fourni
            $deviceToken = $request->input('device_token');
            if ($deviceToken) {
                $user->update([
                    'device_token' => $deviceToken,
                    'device_type' => $request->input('platform', 'unknown'),
                    'notification_provider' => $request->input('notification_provider', 'expo'),
                ]);

                Log::info('Device token saved successfully', [
                    'user_id' => $user->id,
                    'token_saved' => 'SUCCESS'
                ]);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        $isMobile = $request->input('mobile_auth') === 'true' || 
                   $request->header('X-Mobile-App') === 'true' ||
                   $request->expectsJson();

        Log::info('=== LOGOUT REQUEST ===', [
            'is_mobile' => $isMobile,
            'headers' => [
                'user_agent' => $request->header('User-Agent'),
                'x_mobile_app' => $request->header('X-Mobile-App'),
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
            ],
            'mobile_auth_param' => $request->input('mobile_auth'),
            'expects_json' => $request->expectsJson(),
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Réponse différente pour mobile
        if ($isMobile) {
            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);
        }

        return redirect('/');
    }

    /**
     * Associer les annonces guests existantes à l'utilisateur connecté
     */
    private function associateGuestAnnouncements(User $user): void
    {
        try {
            // Récupérer toutes les annonces guests non expirées avec cet email
            $guestAds = \App\Models\Ad::where('is_guest', true)
                ->where('guest_email', $user->email)
                ->where('guest_expires_at', '>', now())
                ->get();

            $associatedCount = 0;
            
            foreach ($guestAds as $ad) {
                // Associer l'annonce à l'utilisateur
                $success = $ad->associateToUser($user);
                if ($success) {
                    $associatedCount++;
                }
            }

            if ($associatedCount > 0) {
                Log::info('Annonces guests associées lors de la connexion', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'associated_count' => $associatedCount
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'association des annonces guests à la connexion', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer la connexion si l'association échoue
        }
    }
}
