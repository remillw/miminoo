<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Ad;

class NewAnnouncementInRadius extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ad $ad,
        public float $distance
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Pas de canal push, on appellera toExpo() manuellement
    }

    public function toMail(object $notifiable): MailMessage
    {
        $parentName = $this->ad->isGuest() ? $this->ad->getOwnerName() : 
                     $this->ad->parent->firstname . ' ' . $this->ad->parent->lastname;
        
        $childrenCount = count($this->ad->children);
        $childrenText = $childrenCount . ' enfant' . ($childrenCount > 1 ? 's' : '');
        
        $date = $this->ad->date_start->format('d/m/Y');
        $time = $this->ad->date_start->format('H:i') . ' - ' . $this->ad->date_end->format('H:i');
        
        $city = '';
        if ($this->ad->address) {
            $addressParts = explode(',', $this->ad->address->address);
            $city = trim(end($addressParts));
        }

        return (new MailMessage)
            ->subject('Nouvelle annonce dans votre secteur - ' . config('app.name'))
            ->greeting('Bonjour ' . $notifiable->firstname . ' !')
            ->line("Une nouvelle annonce vient d'être publiée dans votre secteur d'intervention.")
            ->line("**Détails de la mission :**")
            ->line("👤 Parent : {$parentName}")
            ->line("👶 Enfants : {$childrenText}")
            ->line("📅 Date : {$date}")
            ->line("🕐 Horaires : {$time}")
            ->line("📍 Lieu : {$city} (à " . round($this->distance, 1) . " km de vous)")
            ->line("💰 Tarif : {$this->ad->hourly_rate}€/heure")
            ->action('Voir l\'annonce', route('announcements.show', $this->createAdSlug()))
            ->line('Postulez rapidement pour ne pas manquer cette opportunité !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ad_id' => $this->ad->id,
            'distance' => $this->distance,
            'message' => 'Nouvelle annonce dans votre secteur'
        ];
    }

    /**
     * Envoyer la notification push via Expo
     */
    public function toExpo(object $notifiable)
    {
        // Récupérer tous les tokens de l'utilisateur
        $deviceTokens = DB::table('device_tokens')
            ->where('user_id', $notifiable->id)
            ->pluck('token')
            ->toArray();
            
        if (empty($deviceTokens) && $notifiable->device_token) {
            // Fallback pour la rétrocompatibilité
            $deviceTokens = [$notifiable->device_token];
        }
            
        if (empty($deviceTokens) || !$notifiable->push_notifications) {
            Log::info('Notification non envoyée : pas de device_token ou notifications désactivées', [
                'user_id' => $notifiable->id,
                'has_tokens' => !empty($deviceTokens),
                'push_enabled' => $notifiable->push_notifications
            ]);
            return;
        }

        $parentName = $this->ad->isGuest() ? $this->ad->getOwnerName() : 
                     $this->ad->parent->firstname . ' ' . $this->ad->parent->lastname;
        
        $city = '';
        if ($this->ad->address) {
            $addressParts = explode(',', $this->ad->address->address);
            $city = trim(end($addressParts));
        }

        $title = 'Nouvelle annonce dans votre secteur';
        $body = "Garde d'enfants à {$city} (à " . round($this->distance, 1) . " km) - {$this->ad->hourly_rate}€/h";

        $successCount = 0;
        $failCount = 0;

        foreach ($deviceTokens as $token) {
            Log::info('Envoi notification Expo pour nouvelle annonce', [
                'to' => $token,
                'user_id' => $notifiable->id,
                'ad_id' => $this->ad->id,
                'distance' => $this->distance
            ]);

            $response = Http::post('https://exp.host/--/api/v2/push/send', [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => [
                    'screen' => 'AnnouncementScreen',
                    'param' => [
                        'announcementId' => $this->ad->id,
                        'slug' => $this->createAdSlug(),
                    ],
                ],
            ]);
            
            if ($response->successful()) {
                $successCount++;
            } else {
                $failCount++;
                Log::warning('Échec envoi notification nouvelle annonce', [
                    'token' => $token,
                    'response' => $response->json()
                ]);
            }
        }
 
        return [
            'success' => $successCount,
            'failed' => $failCount
        ];
    }

    private function createAdSlug(): string
    {
        $date = $this->ad->date_start->format('Y-m-d');
        $title = $this->ad->title ? 
            strtolower(preg_replace('/[^a-z0-9]/i', '-', $this->ad->title)) : 'annonce';
        
        $slug = trim($date . '-' . $title . '-' . $this->ad->id, '-');
        return preg_replace('/-+/', '-', $slug);
    }
} 