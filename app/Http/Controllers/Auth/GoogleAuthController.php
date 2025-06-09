<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth provider
     */
    public function redirect(Request $request)
    {
        // Stocker le rôle choisi en session si fourni
        if ($request->has('role')) {
            session(['intended_role' => $request->role]);
        }
        
        return Socialite::driver('google')->redirect();
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

                // Vérifier si l'utilisateur a un rôle
                if (!$existingUser->role_id || !$existingUser->role) {
                    // Utilisateur sans rôle - demander le rôle
                    session([
                        'existing_user_id' => $existingUser->id,
                        'google_user' => [
                            'google_id' => $googleUser->getId(),
                            'email' => $googleUser->getEmail(),
                            'name' => $googleUser->getName(),
                            'avatar' => $googleUser->getAvatar(),
                        ]
                    ]);

                    return Inertia::render('Auth/GoogleRoleSelection', [
                        'existingUser' => true
                    ]);
                }

                // Utilisateur avec rôle - connexion directe
                Auth::login($existingUser);
                return redirect()->intended('/dashboard')->with('success', 'Connexion réussie avec Google !');
            }

            // Nouvel utilisateur - vérifier si un rôle a été choisi avant la redirection
            $intendedRole = session('intended_role');
            
            if ($intendedRole) {
                // Rôle déjà choisi, créer directement le compte
                return $this->createUserWithRole($googleUser, $intendedRole);
            }

            // Pas de rôle choisi - stocker les données et demander le rôle
            session([
                'google_user' => [
                    'google_id' => $googleUser->getId(),
                    'email' => $googleUser->getEmail(),
                    'name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                ]
            ]);

            return Inertia::render('Auth/GoogleRoleSelection', [
                'existingUser' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Google OAuth Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/login')->with('error', 'Erreur lors de la connexion avec Google.');
        }
    }

    public function completeRegistration(Request $request)
    {
        $request->validate([
            'role' => 'required|in:parent,babysitter'
        ]);

        $googleUserData = session('google_user');
        $existingUserId = session('existing_user_id');
        
        if (!$googleUserData) {
            return redirect('/login')->with('error', 'Session Google expirée. Veuillez recommencer.');
        }

        try {
            if ($existingUserId) {
                // Utilisateur existant - mise à jour du rôle
                $user = User::find($existingUserId);
                
                if (!$user) {
                    return redirect('/login')->with('error', 'Utilisateur introuvable.');
                }

                $roleId = $request->role === 'parent' ? 2 : 3;
                $status = $request->role === 'babysitter' ? 'pending' : 'approved';

                $user->update([
                    'role_id' => $roleId,
                    'status' => $status,
                ]);

                // Créer le profil correspondant
                if ($request->role === 'parent') {
                    \App\Models\ParentProfile::create(['user_id' => $user->id]);
                } else {
                    \App\Models\BabysitterProfile::create(['user_id' => $user->id]);
                }

                session()->forget(['google_user', 'existing_user_id', 'intended_role']);
                Auth::login($user);

                if ($user->status === 'pending') {
                    return redirect('/dashboard')->with('info', 'Votre email est vérifié ! Votre demande de babysitter est en attente d\'approbation.');
                }

                return redirect('/dashboard')->with('success', 'Profil complété avec succès !');
            } else {
                // Nouvel utilisateur
                return $this->createUserWithRole($googleUserData, $request->role);
            }

        } catch (\Exception $e) {
            Log::error('Google registration completion error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/login')->with('error', 'Erreur lors de la finalisation du compte.');
        }
    }

    private function createUserWithRole($googleUserData, $role)
    {
        // Déterminer le role_id
        $roleId = $role === 'parent' ? 2 : 3;

        // Séparer le nom complet en prénom et nom
        $name = is_array($googleUserData) ? $googleUserData['name'] : $googleUserData->getName();
        $nameParts = explode(' ', $name, 2);
        $firstname = $nameParts[0];
        $lastname = isset($nameParts[1]) ? $nameParts[1] : '';

        // Créer l'utilisateur
        $user = User::create([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => is_array($googleUserData) ? $googleUserData['email'] : $googleUserData->getEmail(),
            'google_id' => is_array($googleUserData) ? $googleUserData['google_id'] : $googleUserData->getId(),
            'avatar' => is_array($googleUserData) ? $googleUserData['avatar'] : $googleUserData->getAvatar(),
            'role_id' => $roleId,
            'status' => $role === 'parent' ? 'approved' : 'pending',
            'email_verified_at' => now(), // Email automatiquement vérifié avec Google
        ]);

        // Créer le profil correspondant
        if ($role === 'parent') {
            \App\Models\ParentProfile::create(['user_id' => $user->id]);
        } else {
            \App\Models\BabysitterProfile::create(['user_id' => $user->id]);
        }

        Log::info('Google user created successfully:', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $role,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s') ?? 'NULL',
            'google_id' => $user->google_id,
        ]);

        // Supprimer les données de session
        session()->forget(['google_user', 'existing_user_id', 'intended_role']);

        // Connexion automatique
        Auth::login($user);

        // Redirection selon le statut
        if ($user->status === 'pending') {
            return redirect('/dashboard')->with('info', 'Votre demande de babysitter est en attente d\'approbation. Votre email est déjà vérifié !');
        }

        return redirect('/dashboard')->with('success', 'Compte créé avec succès !');
    }
}
