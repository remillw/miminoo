<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        Log::info('=== EMAIL VERIFICATION ATTEMPT ===', [
            'user_id' => $id,
            'hash' => substr($hash, 0, 10) . '...',
            'is_authenticated' => Auth::check(),
            'current_user_id' => Auth::id(),
            'url_signature_valid' => URL::hasValidSignature($request),
        ]);

        // Trouver l'utilisateur par ID
        $user = User::findOrFail($id);
        
        // Vérifier la signature de l'URL
        if (!URL::hasValidSignature($request)) {
            Log::warning('Invalid signature for email verification', ['user_id' => $id]);
            return redirect()->route('connexion')->with('error', 'Lien de vérification invalide ou expiré.');
        }

        // Vérifier le hash
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            Log::warning('Invalid hash for email verification', ['user_id' => $id]);
            return redirect()->route('connexion')->with('error', 'Lien de vérification invalide.');
        }

        // Si l'email est déjà vérifié
        if ($user->hasVerifiedEmail()) {
            Log::info('Email already verified', ['user_id' => $user->id]);
            
            // Si l'utilisateur n'est pas connecté, le connecter automatiquement
            if (!Auth::check()) {
                Auth::login($user, true);
                Log::info('User auto-logged in after email verification', ['user_id' => $user->id]);
            }
            
            return redirect()->route('dashboard')->with('success', 'Votre email est déjà vérifié !');
        }

        // Marquer l'email comme vérifié
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Log::info('Email verified successfully', ['user_id' => $user->id]);
        }

        // Si l'utilisateur n'est pas connecté, le connecter automatiquement
        if (!Auth::check()) {
            Auth::login($user, true);
            Log::info('User auto-logged in after email verification', ['user_id' => $user->id]);
        }

        return redirect()->route('dashboard')->with('success', 'Votre email a été vérifié avec succès !');
    }
}
