<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Dispute $dispute, protected bool $isForAdmin = false)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        if ($this->isForAdmin) {
            return (new MailMessage)
                ->subject('Nouvelle réclamation créée')
                ->view('emails.dispute-created', [
                    'notifiable' => $notifiable,
                    'dispute' => $this->dispute,
                    'isForAdmin' => true
                ]);
        }

        return (new MailMessage)
            ->subject('Votre réclamation a été créée')
            ->view('emails.dispute-created', [
                'notifiable' => $notifiable,
                'dispute' => $this->dispute,
                'isForAdmin' => false
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'reservation_id' => $this->dispute->reservation_id,
            'message' => $this->isForAdmin ? 'Nouvelle réclamation créée' : 'Votre réclamation a été créée',
            'type' => 'dispute_created'
        ];
    }
} 