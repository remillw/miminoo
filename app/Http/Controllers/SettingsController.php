<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Page des paramètres
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $requestedMode = $request->get('mode');
        
        // Déterminer les rôles de l'utilisateur
        $hasParentRole = $user->hasRole('parent');
        $hasBabysitterRole = $user->hasRole('babysitter');

        // Déterminer le mode actuel selon la demande et les rôles
        $currentMode = 'parent'; // Par défaut
        
        if ($requestedMode === 'babysitter' && $hasBabysitterRole) {
            $currentMode = 'babysitter';
        } elseif ($requestedMode === 'parent' && $hasParentRole) {
            $currentMode = 'parent';
        } elseif ($hasBabysitterRole && !$hasParentRole) {
            $currentMode = 'babysitter';
        } elseif ($hasParentRole && !$hasBabysitterRole) {
            $currentMode = 'parent';
        } elseif ($hasBabysitterRole) {
            // Si les deux rôles et pas de mode spécifique, babysitter par défaut
            $currentMode = 'babysitter';
        }

        // Récupérer les préférences de notifications
        $notificationSettings = [
            'email_notifications' => $user->email_notifications ?? true,
            'push_notifications' => $user->push_notifications ?? true,
            'sms_notifications' => $user->sms_notifications ?? false,
        ];

        // Vérifier s'il y a des réservations en cours pour la suppression de compte
        $hasActiveReservations = false;
        if ($user->hasRole('babysitter')) {
            $hasActiveReservations = Reservation::where('babysitter_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        } elseif ($user->hasRole('parent')) {
            $hasActiveReservations = Reservation::where('parent_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        }

        return Inertia::render('Settings/Index', [
            'user' => array_merge($user->toArray(), [
                'profile_photo_url' => $user->getAvatarUrl(),
                'password' => $user->password ? true : false, // Ne pas exposer le hash
                'roles' => $user->roles->toArray(),
            ]),
            'current_mode' => $currentMode,
            'has_parent_role' => $hasParentRole,
            'has_babysitter_role' => $hasBabysitterRole,
            'notification_settings' => $notificationSettings,
            'has_active_reservations' => $hasActiveReservations,
        ]);
    }

    /**
     * Mettre à jour les préférences de notifications
     */
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
        ]);

        $user = $request->user();

        try {
            $user->update([
                'email_notifications' => $request->email_notifications,
                'push_notifications' => $request->push_notifications,
                'sms_notifications' => $request->sms_notifications,
            ]);

            Log::info('Préférences de notifications mises à jour', [
                'user_id' => $user->id,
                'email' => $request->email_notifications,
                'push' => $request->push_notifications,
                'sms' => $request->sms_notifications,
            ]);

            return back()->with('success', 'Préférences de notifications mises à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la mise à jour des préférences');
        }
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // Validation conditionnelle selon si l'utilisateur a déjà un mot de passe
        if ($user->password) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Rules\Password::defaults(), 'confirmed'],
            ]);
        } else {
            $request->validate([
                'password' => ['required', Rules\Password::defaults(), 'confirmed'],
            ]);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            Log::info('Mot de passe mis à jour', [
                'user_id' => $user->id,
                'had_password' => $user->password ? true : false
            ]);

            return back()->with('success', 'Mot de passe mis à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour mot de passe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la mise à jour du mot de passe');
        }
    }


    /**
     * Supprimer le compte utilisateur
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|string|in:SUPPRIMER',
        ]);

        $user = $request->user();

        // Vérifier s'il y a des réservations actives
        $hasActiveReservations = false;
        if ($user->hasRole('babysitter')) {
            $hasActiveReservations = Reservation::where('babysitter_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        } elseif ($user->hasRole('parent')) {
            $hasActiveReservations = Reservation::where('parent_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        }

        if ($hasActiveReservations) {
            return back()->with('error', 'Impossible de supprimer le compte : vous avez des réservations en cours.');
        }

        try {
            // Log de la suppression
            Log::warning('Suppression de compte utilisateur', [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->toArray(),
            ]);

            // Déconnecter l'utilisateur
            auth()->logout();

            // Supprimer l'utilisateur (les relations seront supprimées en cascade)
            $user->delete();

            // Invalider la session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')->with('success', 'Votre compte a été supprimé avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur suppression compte', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la suppression du compte.');
        }
    }
} 