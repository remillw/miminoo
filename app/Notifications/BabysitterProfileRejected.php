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
            ->view('emails.babysitter-profile-rejected', [
                'notifiable' => $notifiable,
                'reason' => $this->reason
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Votre profil babysitter a été rejeté',
            'reason' => $this->reason
        ];
    }
} 