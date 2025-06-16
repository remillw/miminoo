<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialAuthController extends Controller
{
    /**
     * Rediriger vers le provider social
     */
    public function redirectToProvider($provider)
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return redirect()->route('login')->with('error', 'Provider non supporté');
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Gérer le callback du provider social
     */
    public function handleProviderCallback($provider, Request $request)
    {
        try {
            if (!in_array($provider, ['google', 'apple'])) {
                return redirect()->route('login')->with('error', 'Provider non supporté');
            }

            $socialUser = Socialite::driver($provider)->user();
            
            // Chercher un utilisateur existant avec cet ID social
            $user = User::where($provider . '_id', $socialUser->getId())->first();
            
            if ($user) {
                // Utilisateur existant avec ce compte social
                Auth::login($user);
                return $this->redirectAfterLogin($user);
            }

            // Chercher un utilisateur avec le même email
            $existingUser = User::where('email', $socialUser->getEmail())->first();
            
            if ($existingUser) {
                // Lier le compte social à l'utilisateur existant
                $existingUser->update([
                    $provider . '_id' => $socialUser->getId(),
                    'provider' => $provider,
                    'is_social_account' => true,
                    'social_data_locked' => true,
                    'avatar' => $socialUser->getAvatar(),
                ]);
                
                Auth::login($existingUser);
                return $this->redirectAfterLogin($existingUser);
            }

            // Créer un nouvel utilisateur
            $user = User::create([
                'firstname' => $this->extractFirstName($socialUser->getName()),
                'lastname' => $this->extractLastName($socialUser->getName()),
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)), // Mot de passe aléatoire
                $provider . '_id' => $socialUser->getId(),
                'provider' => $provider,
                'is_social_account' => true,
                'social_data_locked' => true,
                'avatar' => $socialUser->getAvatar(),
            ]);

            // Assigner le rôle parent par défaut
            $parentRole = Role::where('name', 'parent')->first();
            if ($parentRole) {
                $user->assignRole($parentRole);
            }

            Auth::login($user);
            
            // Rediriger vers la page de choix de rôle si c'est un nouveau compte
            return redirect()->route('role.selection')->with('success', 'Compte créé avec succès ! Choisissez votre rôle.');

        } catch (\Exception $e) {
            \Log::error('Erreur authentification sociale', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('login')->with('error', 'Erreur lors de la connexion avec ' . ucfirst($provider));
        }
    }

    /**
     * Délier un compte social
     */
    public function unlinkProvider($provider, Request $request)
    {
        $user = $request->user();
        
        if (!in_array($provider, ['google', 'apple'])) {
            return back()->with('error', 'Provider non supporté');
        }

        // Protection spéciale pour les utilisateurs Google uniquement
        if ($provider === 'google' && $user->is_social_account && $user->provider === 'google' && !$user->password) {
            return back()->with('error', 'Pour votre sécurité, vous ne pouvez pas vous déconnecter de Google. Votre compte est entièrement géré par Google. Si vous souhaitez supprimer votre compte, utilisez l\'option de suppression dans les paramètres.');
        }

        // Vérifier que l'utilisateur a un mot de passe ou un autre provider
        if ($user->is_social_account && !$user->password && $user->provider === $provider) {
            return back()->with('error', 'Vous devez d\'abord définir un mot de passe avant de délier ce compte');
        }

        $user->update([
            $provider . '_id' => null,
            'provider' => $user->provider === $provider ? null : $user->provider,
            'is_social_account' => $user->google_id || $user->apple_id ? true : false,
            'social_data_locked' => false,
        ]);

        return back()->with('success', 'Compte ' . ucfirst($provider) . ' délié avec succès');
    }

    /**
     * Redirection après connexion selon le rôle
     */
    private function redirectAfterLogin($user)
    {
        if ($user->hasRole('babysitter')) {
            return redirect()->route('babysitter.dashboard');
        } elseif ($user->hasRole('parent')) {
            return redirect()->route('parent.dashboard');
        } else {
            return redirect()->route('role.selection');
        }
    }

    /**
     * Extraire le prénom du nom complet
     */
    private function extractFirstName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }

    /**
     * Extraire le nom de famille du nom complet
     */
    private function extractLastName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) > 1) {
            array_shift($parts); // Enlever le premier élément (prénom)
            return implode(' ', $parts);
        }
        return '';
    }
} 