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

        // Déterminer le mode selon les rôles de l'utilisateur
        $currentMode = 'parent'; // Par défaut
        if ($user->hasRole('babysitter')) {
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
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'language' => $user->language ?? 'fr',
                'google_id' => $user->google_id,
                'apple_id' => $user->apple_id,
                'is_social_account' => $user->is_social_account,
                'social_data_locked' => $user->social_data_locked,
                'provider' => $user->provider,
                'password' => $user->password ? true : false, // Ne pas exposer le hash
            ],
            'current_mode' => $currentMode,
            'notification_settings' => $notificationSettings,
            'has_active_reservations' => $hasActiveReservations,
            'available_languages' => [
                ['code' => 'fr', 'name' => 'Français'],
                // Ajouter d'autres langues plus tard
            ],
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

            return response()->json([
                'success' => true,
                'message' => 'Préférences de notifications mises à jour avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour des préférences'
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            Log::info('Mot de passe mis à jour', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour mot de passe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour du mot de passe'
            ], 500);
        }
    }

    /**
     * Changer la langue
     */
    public function updateLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|in:fr', // Pour l'instant, seulement français
        ]);

        $user = $request->user();

        try {
            $user->update([
                'language' => $request->language,
            ]);

            // Mettre à jour la session
            session(['locale' => $request->language]);

            Log::info('Langue mise à jour', [
                'user_id' => $user->id,
                'language' => $request->language,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Langue mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour langue', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour de la langue'
            ], 500);
        }
    }

    /**
     * Supprimer le compte utilisateur
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
            'confirmation' => 'required|in:SUPPRIMER',
        ]);

        $user = $request->user();

        try {
            // Vérifier qu'il n'y a pas de réservations en cours
            $activeReservations = collect();

            if ($user->hasRole('babysitter')) {
                $activeReservations = Reservation::where('babysitter_id', $user->id)
                    ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                    ->get();
            } elseif ($user->hasRole('parent')) {
                $activeReservations = Reservation::where('parent_id', $user->id)
                    ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                    ->get();
            }

            if ($activeReservations->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Impossible de supprimer le compte : vous avez des réservations en cours. Veuillez les terminer ou les annuler avant de supprimer votre compte.'
                ], 400);
            }

            // Anonymiser les données plutôt que de supprimer complètement
            $user->update([
                'firstname' => 'Utilisateur',
                'lastname' => 'Supprimé',
                'email' => 'deleted_' . $user->id . '@miminoo.fr',
                'phone' => null,
                'address' => null,
                'date_of_birth' => null,
                'bio' => null,
                'is_active' => false,
                'email_verified_at' => null,
                'password' => Hash::make('deleted_account_' . time()),
            ]);

            // Supprimer les tokens d'accès
            $user->tokens()->delete();

            // Déconnecter l'utilisateur
            auth()->logout();

            Log::info('Compte utilisateur supprimé', [
                'user_id' => $user->id,
                'email' => $request->user()->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre compte a été supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur suppression compte', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression du compte'
            ], 500);
        }
    }
} 