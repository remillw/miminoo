<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BabysitterProfileRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $reason)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre profil babysitter a été rejeté')
            ->greeting('Bonjour,')
            ->line('Votre profil babysitter a été rejeté pour la raison suivante :')
            ->line($this->reason)
            ->action('Modifier mon profil', route('profil'))
            ->line('Vous pouvez modifier votre profil et soumettre une nouvelle demande de vérification.');
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Votre profil babysitter a été rejeté',
            'reason' => $this->reason
        ];
    }
} 