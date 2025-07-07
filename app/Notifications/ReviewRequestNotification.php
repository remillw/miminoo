<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRequestNotification extends Notification
{
    use Queueable;

    public $reservation;
    public $reviewType; // 'parent' ou 'babysitter'
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reservation $reservation, string $reviewType, string $message)
    {
        $this->reservation = $reservation;
        $this->reviewType = $reviewType;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $revieweeType = $this->reviewType === 'parent' ? 'parents' : 'babysitter';
        $revieweeName = $this->reviewType === 'parent' 
            ? $this->reservation->ad->user->first_name . ' ' . $this->reservation->ad->user->last_name
            : $this->reservation->babysitter->first_name . ' ' . $this->reservation->babysitter->last_name;

        return (new MailMessage)
            ->subject('Laissez un avis - Service terminé')
            ->greeting('Bonjour ' . $notifiable->first_name . ' !')
            ->line($this->message)
            ->line("Votre expérience avec {$revieweeName} s'est bien passée ? N'hésitez pas à laisser un avis pour aider la communauté Miminoo.")
            ->action('Laisser un avis', url('/reviews/create?reservation_id=' . $this->reservation->id . '&type=' . $this->reviewType))
            ->line('Vous avez 30 jours pour laisser votre avis.')
            ->line('Merci de faire partie de la communauté Miminoo !');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $revieweeName = $this->reviewType === 'parent' 
            ? $this->reservation->ad->user->first_name . ' ' . $this->reservation->ad->user->last_name
            : $this->reservation->babysitter->first_name . ' ' . $this->reservation->babysitter->last_name;

        return [
            'type' => 'review_request',
            'message' => $this->message,
            'reservation_id' => $this->reservation->id,
            'review_type' => $this->reviewType,
            'reviewee_name' => $revieweeName,
            'expires_at' => now()->addDays(30)->toISOString()
        ];
    }
} 