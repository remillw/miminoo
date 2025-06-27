<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GuestAnnouncementCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Ad $ad
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre annonce a été créée avec succès !')
            ->greeting('Bonjour !')
            ->line('Votre annonce de garde d\'enfants a été créée avec succès.')
            ->line('**Détails de votre annonce :**')
            ->line('📅 **Date :** ' . $this->ad->date_start->format('d/m/Y à H:i'))
            ->line('👶 **Enfants :** ' . count($this->ad->children) . ' enfant' . (count($this->ad->children) > 1 ? 's' : ''))
            ->line('💰 **Tarif :** ' . $this->ad->hourly_rate . '€/h')
            ->line('📍 **Lieu :** ' . $this->ad->address->postal_code)
            ->line('')
            ->line('Vous recevrez une notification par email dès qu\'une babysitter postulera à votre annonce.')
            ->line('**💡 Conseil :** Créez un compte pour gérer toutes vos annonces en un seul endroit et accéder à plus de fonctionnalités.')
            ->action('Créer mon compte', route('register'))
            ->action('Voir toutes les annonces', route('announcements.index'))
            ->line('Merci d\'utiliser TrouvetaBabysitter !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ad_id' => $this->ad->id,
            'guest_token' => $this->ad->guest_token,
        ];
    }
}
