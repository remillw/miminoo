<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // Détecter le type de token et envoyer via la bonne API
        if ($this->isExpoToken($user->device_token)) {
            return $this->sendExpoNotification($user->device_token, $title, $body, $data);
        } else {
            return $this->sendFirebaseNotification($user->device_token, $title, $body, $data);
        }
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
     * Obtenir un token d'accès OAuth2 pour Firebase
     */
    private function getAccessToken(): ?string
    {
        try {
            $serviceAccountPath = config('services.firebase.service_account_path');
            
            if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
                Log::error('Firebase service account file not found', [
                    'path' => $serviceAccountPath
                ]);
                return null;
            }

            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            
            // Créer le JWT pour l'authentification
            $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
            $now = time();
            $payload = json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ]);

            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            // Signature avec la clé privée
            $signature = '';
            openssl_sign(
                $base64Header . '.' . $base64Payload,
                $signature,
                $serviceAccount['private_key'],
                'SHA256'
            );
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

            // Échanger le JWT contre un access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            Log::error('Failed to get Firebase access token', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception while getting Firebase access token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Détecter si le token est un token Expo
     */
    private function isExpoToken(string $token): bool
    {
        return str_starts_with($token, 'ExponentPushToken[');
    }

    /**
     * Envoyer une notification via Expo Push API
     */
    private function sendExpoNotification(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $payload = [
            'to' => $deviceToken,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'badge' => 1,
            'priority' => 'high'
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', $payload);

            if ($response->successful()) {
                Log::info('Expo push notification sent successfully', [
                    'token_preview' => substr($deviceToken, 0, 20) . '...',
                    'title' => $title,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Failed to send Expo push notification', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'token_preview' => substr($deviceToken, 0, 20) . '...'
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending Expo push notification', [
                'error' => $e->getMessage(),
                'token_preview' => substr($deviceToken, 0, 20) . '...'
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification push via Firebase FCM HTTP v1
     */
    private function sendFirebaseNotification(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $projectId = config('services.firebase.project_id');
        $accessToken = $this->getAccessToken();
        
        if (!$projectId || !$accessToken) {
            Log::error('Firebase configuration missing', [
                'project_id' => !!$projectId,
                'access_token' => !!$accessToken
            ]);
            return false;
        }

        $payload = [
            'message' => [
                'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                ],
                'data' => array_map('strval', $data), // FCM requires string values
                'android' => [
                    'notification' => [
                'sound' => 'default',
                        'priority' => 'high'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

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