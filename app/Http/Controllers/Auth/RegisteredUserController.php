<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\ParentProfile;
use App\Models\BabysitterProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'firstname' => 'required|string|min:2|max:50|regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/',
            'lastname' => 'required|string|min:2|max:50|regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'accepted' => 'required|accepted',
        ]);

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'pending', // En attente de configuration des rôles
        ]);

        event(new Registered($user));

        // Stocker l'utilisateur en session pour la sélection de rôle
        session(['newly_registered_user_id' => $user->id]);

        // Rediriger vers la page de sélection de rôle
        return redirect()->route('role.selection')->with('success', 'Compte créé avec succès ! Choisissez maintenant vos rôles.');
    }

    /**
     * Afficher la page de sélection de rôle pour les nouveaux utilisateurs
     */
    public function roleSelection(): Response|RedirectResponse
    {
        // Cas 1: Utilisateur inscrit classiquement (avec session)
        if (session('newly_registered_user_id')) {
            return Inertia::render('auth/GoogleRoleSelection', [
                'existingUser' => false,
                'isGoogleUser' => false
            ]);
        }
        
        // Cas 2: Utilisateur connecté via Google sans rôles
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->roles()->count() === 0) {
                return Inertia::render('auth/GoogleRoleSelection', [
                    'existingUser' => true,
                    'isGoogleUser' => $user->is_social_account ?? false
                ]);
            }
        }
        
        // Cas 3: Utilisateur déjà configuré - rediriger vers dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        // Cas 4: Pas de session et pas connecté - rediriger vers login
        return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
    }

    /**
     * Traiter la sélection de rôle pour les nouveaux utilisateurs
     */
    public function completeRegistration(Request $request): RedirectResponse
    {
        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:parent,babysitter'
        ]);

        // Cas 1: Utilisateur inscrit classiquement (avec session)
        $userId = session('newly_registered_user_id');
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                return redirect()->route('login')->with('error', 'Utilisateur introuvable.');
            }
        }
        // Cas 2: Utilisateur connecté via Google sans rôles
        elseif (Auth::check()) {
            $user = Auth::user();
            if (!$user || $user->roles()->count() > 0) {
                return redirect()->route('dashboard')->with('error', 'Vous avez déjà configuré vos rôles.');
            }
        }
        // Cas 3: Aucun utilisateur valide
        else {
            return redirect()->route('login')->with('error', 'Session expirée. Veuillez vous connecter.');
        }

        try {
            // Assigner les rôles via la table pivot et créer les profils
            foreach ($request->roles as $roleName) {
                $user->assignRole($roleName);
                
                if ($roleName === 'parent') {
                    ParentProfile::firstOrCreate(['user_id' => $user->id]);
                } elseif ($roleName === 'babysitter') {
                    BabysitterProfile::firstOrCreate(['user_id' => $user->id]);
                }
            }

            // Assigner un avatar aléatoire selon les rôles si pas déjà défini
            if (!$user->avatar || $user->avatar === '/default-avatar.png') {
                $user->avatar = $this->getRandomAvatar($request->roles);
                $user->save();
            }

            // Définir le statut : pending si babysitter inclus, sinon approved
            $status = in_array('babysitter', $request->roles) ? 'pending' : 'approved';
            $user->update(['status' => $status]);

            // Associer les annonces guests existantes avec cet email
            $this->associateGuestAnnouncements($user);

            // Supprimer la session
            session()->forget('newly_registered_user_id');

            // Connexion automatique
            Auth::login($user);

            if ($user->status === 'pending') {
                return redirect()->route('dashboard')->with('info', 'Votre profil babysitter est en attente d\'approbation par notre équipe.');
            }

            return redirect()->route('dashboard')->with('success', 'Inscription terminée avec succès !');

        } catch (\Exception $e) {
            return redirect()->route('role.selection')->with('error', 'Erreur lors de la configuration des rôles.');
        }
    }

    /**
     * Associer les annonces guests existantes à l'utilisateur nouvellement inscrit
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
                Log::info('Annonces guests associées', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'associated_count' => $associatedCount
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'association des annonces guests', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer l'inscription si l'association échoue
        }
    }

    /**
     * Obtenir un avatar aléatoire selon les rôles de l'utilisateur
     */
    private function getRandomAvatar(array $roles): string
    {
        // Déterminer le type d'avatar selon les rôles
        if (in_array('parent', $roles) && in_array('babysitter', $roles)) {
            // Les deux rôles
            $avatarNumber = rand(1, 4);
            return "/storage/avatar/les deux/parent-babysitter-generique{$avatarNumber}.svg";
        } elseif (in_array('parent', $roles)) {
            // Parent uniquement
            $avatarNumber = rand(1, 4);
            return "/storage/avatar/parent/parent-generique{$avatarNumber}.svg";
        } elseif (in_array('babysitter', $roles)) {
            // Babysitter uniquement
            $avatarNumber = rand(1, 4);
            return "/storage/avatar/babysitter/babysitters-generique{$avatarNumber}.svg";
        }

        // Fallback sur un avatar par défaut
        return '/default-avatar.png';
    }
}
