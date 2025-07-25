<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeviceTokenController extends Controller
{
    /**
     * Enregistrer ou mettre à jour le device token de l'utilisateur
     */
    public function store(Request $request)
    {
        Log::info('DeviceTokenController->store() appelé', [
            'request_data' => $request->all(),
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check(),
        ]);

        $request->validate([
            'device_token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
            'notification_provider' => 'nullable|string|in:onesignal,native,capacitor'
        ]);

        $user = Auth::user();
        
        if (!$user) {
            Log::error('DeviceTokenController: Utilisateur non authentifié');
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        Log::info('DeviceTokenController: Début mise à jour device token', [
            'user_id' => $user->id,
            'token_length' => strlen($request->device_token),
            'platform' => $request->platform,
            'provider' => $request->notification_provider ?? 'capacitor'
        ]);

        try {
            // Mettre à jour le device token
            $updateResult = $user->update([
                'device_token' => $request->device_token,
                'device_type' => $request->platform,
                'notification_provider' => $request->notification_provider ?? 'capacitor',
                'device_token_updated_at' => now(),
                'push_notifications' => true, // Activer les push par défaut quand on enregistre un token
            ]);

            Log::info('DeviceTokenController: Mise à jour terminée', [
                'update_success' => $updateResult,
                'user_id' => $user->id,
                'device_type' => $request->platform,
                'notification_provider' => $request->notification_provider ?? 'capacitor',
                'token_preview' => substr($request->device_token, 0, 20) . '...',
                'device_token_updated_at' => $user->fresh()->device_token_updated_at
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device token enregistré avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('DeviceTokenController: Erreur lors de la mise à jour', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du token'
            ], 500);
        }
    }

    /**
     * Supprimer le device token (déconnexion ou désactivation des notifications)
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $user->update([
            'device_token' => null,
            'device_type' => null,
            'device_token_updated_at' => null,
        ]);

        Log::info('Device token supprimé', [
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device token supprimé avec succès'
        ]);
    }

    /**
     * Mettre à jour les préférences de notifications push
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'push_notifications' => 'required|boolean'
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $user->update([
            'push_notifications' => $request->push_notifications
        ]);

        Log::info('Préférences push mises à jour', [
            'user_id' => $user->id,
            'push_enabled' => $request->push_notifications
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour'
        ]);
    }

    /**
     * Nettoyer le flag de session pour l'enregistrement du device token
     */
    public function clearRegistrationFlag(Request $request)
    {
        $request->session()->forget('trigger_device_token_registration');
        
        Log::info('Flag d\'enregistrement device token nettoyé', [
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Flag nettoyé avec succès'
        ]);
    }
}
