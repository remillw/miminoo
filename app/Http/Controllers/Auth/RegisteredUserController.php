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
            'role' => 'required|in:parent,babysitter',
            'accepted' => 'required|accepted',
        ]);

        // Déterminer le role_id : 2 = parent, 3 = babysitter
        $roleId = $request->role === 'parent' ? 2 : 3;

        // Déterminer le statut selon le rôle
        $status = $request->role === 'babysitter' ? 'pending' : 'approved';

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $roleId,
            'status' => $status,
        ]);

        // Créer le profil correspondant selon le rôle
        if ($request->role === 'parent') {
            ParentProfile::create([
                'user_id' => $user->id,
            ]);
        } elseif ($request->role === 'babysitter') {
            BabysitterProfile::create([
                'user_id' => $user->id,
            ]);
        }

        event(new Registered($user));

        // Connexion automatique seulement pour les parents
        if ($request->role === 'parent') {
            Auth::login($user);
            return to_route('dashboard');
        } else {
            // Pour les babysitters, redirection vers une page d'attente
            return to_route('login')->with('status', 'Votre compte babysitter a été créé et est en attente de validation par nos modérateurs. Vous recevrez un email de confirmation une fois votre compte approuvé.');
        }
    }
}
