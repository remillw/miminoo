<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $announcement;
    protected $reason;
    protected $note;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ad $announcement, string $reason, ?string $note = null)
    {
        $this->announcement = $announcement;
        $this->reason = $reason;
        $this->note = $note;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reasonText = $this->getReasonText();
        
        $mailMessage = (new MailMessage)
            ->subject('Annonce annulée - ' . $this->announcement->title)
            ->greeting('Bonjour ' . $notifiable->firstname . ',')
            ->line('L\'annonce "' . $this->announcement->title . '" à laquelle vous aviez postulé a été annulée par le parent.')
            ->line('**Raison :** ' . $reasonText);

        if ($this->note) {
            $mailMessage->line('**Message du parent :** ' . $this->note);
        }

        $mailMessage
            ->line('Toutes les candidatures pour cette annonce ont été automatiquement archivées.')
            ->line('Si vous aviez déjà été payée pour cette garde, vous recevrez des informations de remboursement séparément.')
            ->line('Nous vous encourageons à consulter d\'autres annonces disponibles sur la plateforme.')
            ->action('Voir les annonces', url('/annonces'))
            ->line('Merci de votre compréhension.');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'announcement_cancelled',
            'announcement_id' => $this->announcement->id,
            'announcement_title' => $this->announcement->title,
            'reason' => $this->reason,
            'note' => $this->note,
            'cancelled_at' => now()->toISOString(),
        ];
    }

    /**
     * Get human readable reason text
     */
    private function getReasonText(): string
    {
        return match($this->reason) {
            'found_other_solution' => 'Le parent a trouvé une autre solution',
            'no_longer_needed' => 'Le parent n\'a plus besoin de garde',
            'date_changed' => 'Les dates ont changé',
            'budget_issues' => 'Problème de budget',
            'other' => 'Autre raison',
            default => 'Raison non spécifiée'
        };
    }
} 