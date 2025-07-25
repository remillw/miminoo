<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;
use App\Http\Helpers\MobileDetectionHelper;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth provider
     */
    public function redirect(Request $request)
    {
        try {
            Log::info('Google auth redirect requested', [
                'mobile_param' => $request->get('mobile'),
                'user_agent' => $request->header('User-Agent'),
                'is_capacitor' => MobileDetectionHelper::isCapacitorApp($request),
                'request_url' => $request->fullUrl(),
                'session_id' => session()->getId(),
            ]);

            // Si c'est une requête mobile, marquer la session
            if ($request->get('mobile') === '1' || MobileDetectionHelper::isCapacitorApp($request)) {
                session(['google_mobile_auth' => true]);
                Log::info('Google auth redirect from mobile app', [
                    'mobile_param' => $request->get('mobile'),
                    'is_capacitor' => MobileDetectionHelper::isCapacitorApp($request),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }
            
            $redirectUrl = Socialite::driver('google')->redirect();
            Log::info('Google redirect URL generated', [
                'redirect_url' => $redirectUrl->getTargetUrl(),
            ]);
            
            return $redirectUrl;
            
        } catch (\Exception $e) {
            Log::error('Error in Google auth redirect', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect('/connexion')->with('error', 'Erreur lors de la redirection Google');
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            Log::info('Google user data:', [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            // Vérifier si l'utilisateur existe déjà
            $existingUser = User::where('email', $googleUser->getEmail())->first();

            if ($existingUser) {
                // Utilisateur existant
                
                // Mettre à jour les infos Google et vérifier l'email
                $updateData = [
                    'email_verified_at' => now(), // Vérifier l'email automatiquement avec Google
                ];
                
                if (!$existingUser->google_id) {
                    $updateData['google_id'] = $googleUser->getId();
                }
                
                if (!$existingUser->avatar) {
                    $updateData['avatar'] = $googleUser->getAvatar();
                }
                
                $existingUser->update($updateData);

                Log::info('Email verified for existing Google user:', [
                    'user_id' => $existingUser->id,
                    'email' => $existingUser->email,
                    'email_verified_at' => $existingUser->fresh()->email_verified_at?->format('Y-m-d H:i:s') ?? 'NULL',
                ]);

                // Vérifier si l'utilisateur a des rôles configurés
                $hasRoles = $existingUser->roles()->exists();
                
                Log::info('Checking user roles for Google login:', [
                    'user_id' => $existingUser->id,
                    'email' => $existingUser->email,
                    'has_roles' => $hasRoles,
                    'roles_count' => $existingUser->roles()->count(),
                    'roles_list' => $existingUser->roles()->pluck('name')->toArray(),
                ]);

                if (!$hasRoles) {
                    // Utilisateur sans rôles configurés - demander les rôles
                    Log::info('User has no roles, redirecting to role selection:', [
                        'user_id' => $existingUser->id,
                        'email' => $existingUser->email,
                    ]);
                    
                    session([
                        'existing_user_id' => $existingUser->id,
                        'google_user' => [
                            'google_id' => $googleUser->getId(),
                            'email' => $googleUser->getEmail(),
                            'name' => $googleUser->getName(),
                            'avatar' => $googleUser->getAvatar(),
                        ]
                    ]);

                    return Inertia::render('auth/GoogleRoleSelection', [
                        'existingUser' => true
                    ]);
                }

                // Utilisateur avec rôles configurés - connexion directe
                $isMobileAuth = session('google_mobile_auth', false) || MobileDetectionHelper::isCapacitorApp($request);
                
                Log::info('User has roles, logging in directly:', [
                    'user_id' => $existingUser->id,
                    'email' => $existingUser->email,
                    'roles' => $existingUser->roles()->pluck('name')->toArray(),
                    'is_mobile' => $isMobileAuth,
                    'session_mobile' => session('google_mobile_auth', false),
                ]);
                
                Auth::login($existingUser);
                
                // Déclencher l'enregistrement du device token pour les apps mobiles
                if ($isMobileAuth) {
                    Log::info('Google OAuth mobile auth - Full request data', [
                        'user_id' => $existingUser->id,
                        'device_token' => $request->input('device_token'),
                        'platform' => $request->input('platform'),
                        'notification_provider' => $request->input('notification_provider'),
                        'all_request_data' => $request->all(),
                        'user_agent' => $request->header('User-Agent'),
                    ]);

                    // Save device token directly if provided
                    $deviceToken = $request->input('device_token');
                    if ($deviceToken) {
                        try {
                            $existingUser->update([
                                'device_token' => $deviceToken,
                                'device_type' => $request->input('platform', 'unknown'),
                                'notification_provider' => $request->input('notification_provider', 'capacitor'),
                                'device_token_updated_at' => now(),
                                'push_notifications' => true,
                            ]);

                            Log::info('Device token saved during Google OAuth login', [
                                'user_id' => $existingUser->id,
                                'device_type' => $request->input('platform', 'unknown'),
                                'notification_provider' => $request->input('notification_provider', 'capacitor'),
                                'token_preview' => substr($deviceToken, 0, 20) . '...'
                            ]);

                        } catch (\Exception $e) {
                            Log::error('Failed to save device token during Google OAuth login', [
                                'user_id' => $existingUser->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } else {
                        // Fallback: set session flag for frontend to handle
                        session(['trigger_device_token_registration' => true]);
                        Log::info('No device token in Google OAuth request, setting session flag for frontend handling', [
                            'user_id' => $existingUser->id
                        ]);
                    }

                    session()->forget('google_mobile_auth'); // Nettoyer après usage
                }
                
                // Ajuster la redirection selon l'environnement
                if ($isMobileAuth) {
                    // Pour mobile : toujours rediriger vers le callback mobile
                    Log::info('Redirecting to mobile callback for authenticated user');
                    return redirect('/mobile/callback')->with('success', 'Connexion réussie avec Google !');
                } else {
                    // Pour web : redirection normale
                    return redirect()->intended('/tableau-de-bord')->with('success', 'Connexion réussie avec Google !');
                }
            }

            // Nouvel utilisateur - créer le compte et demander les rôles
            $isMobileAuth = session('google_mobile_auth', false) || MobileDetectionHelper::isCapacitorApp($request);
            
            Log::info('New Google user, redirecting to role selection:', [
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'is_mobile' => $isMobileAuth,
            ]);
            
            session([
                'google_user' => [
                    'google_id' => $googleUser->getId(),
                    'email' => $googleUser->getEmail(),
                    'name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                ]
            ]);

            return Inertia::render('auth/GoogleRoleSelection', [
                'existingUser' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Google OAuth Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/connexion')->with('error', 'Erreur lors de la connexion avec Google.');
        }
    }

    public function completeRegistration(Request $request)
    {
        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:parent,babysitter'
        ]);

        $googleUserData = session('google_user');
        $existingUserId = session('existing_user_id');
        
        if (!$googleUserData) {
            return redirect('/connexion')->with('error', 'Session Google expirée. Veuillez recommencer.');
        }

        try {
            if ($existingUserId) {
                // Utilisateur existant - configuration des rôles
                $user = User::find($existingUserId);
                
                if (!$user) {
                    return redirect('/connexion')->with('error', 'Utilisateur introuvable.');
                }

                // Assigner les rôles via la table pivot
                foreach ($request->roles as $roleName) {
                    $user->assignRole($roleName);
                    
                    // Créer les profils correspondants
                    if ($roleName === 'parent') {
                        \App\Models\ParentProfile::firstOrCreate(['user_id' => $user->id]);
                    } elseif ($roleName === 'babysitter') {
                        \App\Models\BabysitterProfile::firstOrCreate(['user_id' => $user->id]);
                    }
                }

                // Définir le statut : pending si babysitter inclus, sinon approved
                $status = in_array('babysitter', $request->roles) ? 'pending' : 'approved';
                $user->update(['status' => $status]);

                session()->forget(['google_user', 'existing_user_id']);
                
                // ✅ AMÉLIORATION: Nettoyer la session mobile après usage
                $isMobile = MobileDetectionHelper::isCapacitorApp($request);
                if ($isMobile) {
                    session()->forget('google_mobile_auth');
                }
                
                Auth::login($user);
                
                // Déclencher l'enregistrement du device token pour les apps mobiles
                if ($isMobile) {
                    session(['trigger_device_token_registration' => true]);
                }

                $redirectUrl = MobileDetectionHelper::getRedirectUrl($request, '/tableau-de-bord');
                
                if ($user->status === 'pending') {
                    return redirect($redirectUrl)->with('info', 'Votre email est vérifié ! Votre profil babysitter est en attente d\'approbation.');
                }

                return redirect($redirectUrl)->with('success', 'Profils configurés avec succès !');
            } else {
                // Nouvel utilisateur
                return $this->createUserWithRoles($googleUserData, $request->roles);
            }

        } catch (\Exception $e) {
            Log::error('Google registration completion error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/connexion')->with('error', 'Erreur lors de la finalisation du compte.');
        }
    }

    private function createUserWithRoles($googleUserData, $roles)
    {
        // Séparer le nom complet en prénom et nom
        $name = is_array($googleUserData) ? $googleUserData['name'] : $googleUserData->getName();
        $nameParts = explode(' ', $name, 2);
        $firstname = $nameParts[0];
        $lastname = isset($nameParts[1]) ? $nameParts[1] : '';

        // Définir le statut : pending si babysitter inclus, sinon approved
        $status = in_array('babysitter', $roles) ? 'pending' : 'approved';

        // Créer l'utilisateur sans rôle prédéfini
        $user = User::create([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => is_array($googleUserData) ? $googleUserData['email'] : $googleUserData->getEmail(),
            'google_id' => is_array($googleUserData) ? $googleUserData['google_id'] : $googleUserData->getId(),
            'avatar' => is_array($googleUserData) ? $googleUserData['avatar'] : $googleUserData->getAvatar(),
            'status' => $status,
            'email_verified_at' => now(), // Email automatiquement vérifié avec Google
        ]);

        // Assigner les rôles via la table pivot et créer les profils
        foreach ($roles as $roleName) {
            $user->assignRole($roleName);
            
            if ($roleName === 'parent') {
                \App\Models\ParentProfile::create(['user_id' => $user->id]);
            } elseif ($roleName === 'babysitter') {
                \App\Models\BabysitterProfile::create(['user_id' => $user->id]);
            }
        }

        Log::info('Google user created successfully:', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => $roles,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s') ?? 'NULL',
            'google_id' => $user->google_id,
        ]);

        // Supprimer les données de session
        session()->forget(['google_user', 'existing_user_id']);

        // ✅ AMÉLIORATION: Nettoyer la session mobile après usage
        $isMobile = MobileDetectionHelper::isCapacitorApp(request());
        if ($isMobile) {
            session()->forget('google_mobile_auth');
        }

        // Connexion automatique
        Auth::login($user);
        
        // Déclencher l'enregistrement du device token pour les apps mobiles
        if ($isMobile) {
            Log::info('Google OAuth new user creation - Full request data', [
                'user_id' => $user->id,
                'device_token' => request()->input('device_token'),
                'platform' => request()->input('platform'),
                'notification_provider' => request()->input('notification_provider'),
                'all_request_data' => request()->all(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            // Save device token directly if provided
            $deviceToken = request()->input('device_token');
            if ($deviceToken) {
                try {
                    $user->update([
                        'device_token' => $deviceToken,
                        'device_type' => request()->input('platform', 'unknown'),
                        'notification_provider' => request()->input('notification_provider', 'capacitor'),
                        'device_token_updated_at' => now(),
                        'push_notifications' => true,
                    ]);

                    Log::info('Device token saved during Google OAuth new user creation', [
                        'user_id' => $user->id,
                        'device_type' => request()->input('platform', 'unknown'),
                        'notification_provider' => request()->input('notification_provider', 'capacitor'),
                        'token_preview' => substr($deviceToken, 0, 20) . '...'
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to save device token during Google OAuth new user creation', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                // Fallback: set session flag for frontend to handle
                session(['trigger_device_token_registration' => true]);
                Log::info('No device token in Google OAuth new user request, setting session flag for frontend handling', [
                    'user_id' => $user->id
                ]);
            }
        }

        // Redirection selon le statut et l'environnement
        $redirectUrl = MobileDetectionHelper::getRedirectUrl(request(), '/tableau-de-bord');
        
        if ($user->status === 'pending') {
            return redirect($redirectUrl)->with('info', 'Votre profil babysitter est en attente d\'approbation. Votre email est déjà vérifié !');
        }

        return redirect($redirectUrl)->with('success', 'Compte créé avec succès !');
    }
}
    