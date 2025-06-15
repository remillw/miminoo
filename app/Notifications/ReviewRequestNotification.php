<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Reservation $reservation, protected string $userRole)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $otherUserName = $this->userRole === 'parent' 
            ? $this->reservation->babysitter->firstname 
            : $this->reservation->parent->firstname;

        return (new MailMessage)
            ->subject('Laissez un avis sur votre expérience')
            ->greeting('Bonjour ' . $notifiable->firstname . ' !')
            ->line('Votre service de babysitting avec ' . $otherUserName . ' est maintenant terminé.')
            ->line('Votre avis est important pour la communauté et aide les autres utilisateurs à faire leur choix.')
            ->action('Laisser un avis', route('reviews.create', ['reservation' => $this->reservation->id]))
            ->line('Vous avez 7 jours pour laisser votre avis.')
            ->line('Merci de faire confiance à ' . config('app.name') . ' !');
    }

    public function toArray($notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'message' => 'Laissez un avis sur votre expérience',
            'type' => 'review_request'
        ];
    }
} 