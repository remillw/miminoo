<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password page.
     */
    public function show(): Response
    {
        $user = auth()->user();
        
        // Si l'utilisateur utilise uniquement Google, pas besoin de confirmation
        if ($user && $user->isGoogleOnlyUser()) {
            session()->put('auth.password_confirmed_at', time());
            return redirect()->intended(route('dashboard', absolute: false));
        }
        
        return Inertia::render('auth/ConfirmPassword');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Si l'utilisateur utilise uniquement Google, confirmation automatique
        if ($user && $user->isGoogleOnlyUser()) {
            $request->session()->put('auth.password_confirmed_at', time());
            return redirect()->intended(route('dashboard', absolute: false));
        }
        
        if (! Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
