<?php

namespace App\Notifications;

use App\Models\Review;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $review;
    protected $reviewer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review, User $reviewer)
    {
        $this->review = $review;
        $this->reviewer = $reviewer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $stars = str_repeat('⭐', $this->review->rating);
        
        return (new MailMessage)
            ->subject('Nouvel avis reçu sur Miminoo !')
            ->greeting('Bonjour ' . $notifiable->firstname . ' !')
            ->line('Vous avez reçu un nouvel avis de ' . $this->reviewer->firstname . ' ' . $this->reviewer->lastname . '.')
            ->line('Note attribuée : ' . $stars . ' (' . $this->review->rating . '/5)')
            ->when($this->review->comment, function($mail) {
                return $mail->line('Commentaire : "' . $this->review->comment . '"');
            })
            ->action('Voir mon profil', route('profil'))
            ->line('Félicitations pour ce nouvel avis ! Continuez comme ça pour maintenir votre excellente réputation.')
            ->salutation('L\'équipe Miminoo');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_received',
            'review_id' => $this->review->id,
            'reviewer_id' => $this->reviewer->id,
            'reviewer_name' => $this->reviewer->firstname . ' ' . $this->reviewer->lastname,
            'rating' => $this->review->rating,
            'comment' => $this->review->comment,
            'message' => 'Vous avez reçu un avis ' . $this->review->rating . '/5 de ' . $this->reviewer->firstname . ' ' . $this->reviewer->lastname,
        ];
    }
}
