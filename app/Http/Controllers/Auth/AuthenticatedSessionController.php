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
        $request->authenticate();

        $request->session()->regenerate();

        // Associer les annonces guests existantes avec cet email
        $this->associateGuestAnnouncements(Auth::user());

        // Vérifier si c'est une connexion mobile pour déclencher l'enregistrement du device token
        $isMobileAuth = $request->header('X-Mobile-App') === 'true' || 
                       $request->input('mobile_auth') === 'true' ||
                       session('mobile_auth', false);

        if ($isMobileAuth) {
            // Log all mobile login data for debugging
            Log::info('Mobile auth detected - Full request data', [
                'user_id' => Auth::id(),
                'mobile_auth_header' => $request->header('X-Mobile-App'),
                'mobile_auth_input' => $request->input('mobile_auth'),
                'session_mobile' => session('mobile_auth', false),
                'device_token' => $request->input('device_token'),
                'platform' => $request->input('platform'),
                'notification_provider' => $request->input('notification_provider'),
                'all_request_data' => $request->all(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            // Save device token directly if provided
            $deviceToken = $request->input('device_token');
            if ($deviceToken) {
                $user = Auth::user();
                try {
                    $user->update([
                        'device_token' => $deviceToken,
                        'device_type' => $request->input('platform', 'unknown'),
                        'notification_provider' => $request->input('notification_provider', 'capacitor'),
                        'device_token_updated_at' => now(),
                        'push_notifications' => true,
                    ]);

                    Log::info('Device token saved during login', [
                        'user_id' => $user->id,
                        'device_type' => $request->input('platform', 'unknown'),
                        'notification_provider' => $request->input('notification_provider', 'capacitor'),
                        'token_preview' => substr($deviceToken, 0, 20) . '...'
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to save device token during login', [
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                // Fallback: set session flag for frontend to handle
                session(['trigger_device_token_registration' => true]);
                Log::info('No device token in request, setting session flag for frontend handling', [
                    'user_id' => Auth::id()
                ]);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
