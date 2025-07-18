<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Envoyer une notification push à un utilisateur spécifique
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->device_token || !$user->push_notifications) {
            Log::info('Push notification skipped', [
                'user_id' => $user->id,
                'reason' => !$user->device_token ? 'no_token' : 'notifications_disabled'
            ]);
            return false;
        }

        return $this->sendNotification($user->device_token, $title, $body, $data);
    }

    /**
     * Envoyer des notifications push à plusieurs utilisateurs
     */
    public function sendToUsers(array $users, string $title, string $body, array $data = []): array
    {
        $results = [];
        
        foreach ($users as $user) {
            if ($user instanceof User) {
                $results[$user->id] = $this->sendToUser($user, $title, $body, $data);
            }
        }

        return $results;
    }

    /**
     * Envoyer une notification push via Firebase FCM
     */
    private function sendNotification(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.firebase.server_key');
        
        if (!$serverKey) {
            Log::error('Firebase server key not configured');
            return false;
        }

        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info('Push notification sent successfully', [
                    'token_preview' => substr($deviceToken, 0, 20) . '...',
                    'title' => $title
                ]);
                return true;
            } else {
                Log::error('Failed to send push notification', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'token_preview' => substr($deviceToken, 0, 20) . '...'
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending push notification', [
                'error' => $e->getMessage(),
                'token_preview' => substr($deviceToken, 0, 20) . '...'
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification d'annonce dans le rayon
     */
    public function sendAnnouncementNotification(User $user, $ad, float $distance): bool
    {
        $parentName = $ad->isGuest() ? $ad->getOwnerName() : 
                     $ad->parent->firstname . ' ' . $ad->parent->lastname;
        
        $city = '';
        if ($ad->address) {
            $addressParts = explode(',', $ad->address->address);
            $city = trim(end($addressParts));
        }

        $title = "Nouvelle annonce dans votre secteur";
        $body = "Mission à {$city} (" . round($distance, 1) . " km) - {$ad->hourly_rate}€/h";
        
        $data = [
            'type' => 'new_announcement',
            'ad_id' => (string) $ad->id,
            'distance' => (string) $distance,
            'action_url' => route('announcements.show', $ad->id) // Ajuste selon tes routes
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }
} 