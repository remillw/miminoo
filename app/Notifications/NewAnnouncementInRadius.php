<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Ad;
use App\Services\PushNotificationService;

class NewAnnouncementInRadius extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ad $ad,
        public float $distance
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];
        
        // Ajouter les push notifications si l'utilisateur a un device token et les notifications activées
        if ($notifiable->device_token && $notifiable->push_notifications) {
            $channels[] = 'push';
        }
        
        return $channels;
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
     * Envoyer la notification push
     */
    public function toPush(object $notifiable)
    {
        $pushService = app(PushNotificationService::class);
        return $pushService->sendAnnouncementNotification($notifiable, $this->ad, $this->distance);
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