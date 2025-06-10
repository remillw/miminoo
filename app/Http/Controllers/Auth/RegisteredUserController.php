<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\ParentProfile;
use App\Models\BabysitterProfile;
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
    public function roleSelection(): Response
    {
        if (!session('newly_registered_user_id')) {
            return redirect()->route('login')->with('error', 'Session expirée. Veuillez vous connecter.');
        }

        return Inertia::render('auth/GoogleRoleSelection', [
            'existingUser' => false,
            'isGoogleUser' => false
        ]);
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

        $userId = session('newly_registered_user_id');
        
        if (!$userId) {
            return redirect()->route('login')->with('error', 'Session expirée. Veuillez vous connecter.');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login')->with('error', 'Utilisateur introuvable.');
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

            // Définir le statut : pending si babysitter inclus, sinon approved
            $status = in_array('babysitter', $request->roles) ? 'pending' : 'approved';
            $user->update(['status' => $status]);

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
}
