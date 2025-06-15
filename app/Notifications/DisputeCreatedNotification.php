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
                ->greeting('Bonjour,')
                ->line('Une nouvelle réclamation a été créée par ' . $this->dispute->reporter->firstname . ' ' . $this->dispute->reporter->lastname)
                ->line('Motif : ' . $this->dispute->reason)
                ->line('Réservation concernée : #' . $this->dispute->reservation->id)
                ->action('Gérer la réclamation', route('admin.disputes.show', $this->dispute))
                ->line('Merci de traiter cette réclamation rapidement.');
        }

        return (new MailMessage)
            ->subject('Votre réclamation a été créée')
            ->greeting('Bonjour ' . $notifiable->firstname . ' !')
            ->line('Votre réclamation a été créée avec succès.')
            ->line('Notre équipe va examiner votre demande dans les plus brefs délais.')
            ->line('Vous recevrez une réponse sous 48h maximum.')
            ->action('Voir ma réclamation', route('disputes.show', $this->dispute))
            ->line('Merci de votre patience.');
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