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
        $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'required|in:ios,android,web'
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // Mettre à jour le device token
        $user->update([
            'device_token' => $request->device_token,
            'device_type' => $request->device_type,
            'device_token_updated_at' => now(),
            'push_notifications' => true, // Activer les push par défaut quand on enregistre un token
        ]);

        Log::info('Device token enregistré', [
            'user_id' => $user->id,
            'device_type' => $request->device_type,
            'token_preview' => substr($request->device_token, 0, 20) . '...'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device token enregistré avec succès'
        ]);
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
}
