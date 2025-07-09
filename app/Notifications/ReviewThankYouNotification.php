<?php

namespace App\Notifications;

use App\Models\Review;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewThankYouNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $review;
    protected $reviewedUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review, User $reviewedUser)
    {
        $this->review = $review;
        $this->reviewedUser = $reviewedUser;
    }

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
        $stars = str_repeat('⭐', $this->review->rating);
        
        return (new MailMessage)
            ->subject('Merci pour votre avis sur Miminoo !')
            ->greeting('Bonjour ' . $notifiable->firstname . ' !')
            ->line('Merci d\'avoir pris le temps de laisser un avis sur ' . $this->reviewedUser->firstname . ' ' . $this->reviewedUser->lastname . '.')
            ->line('Votre note : ' . $stars . ' (' . $this->review->rating . '/5)')
            ->when($this->review->comment, function($mail) {
                return $mail->line('Votre commentaire : "' . $this->review->comment . '"');
            })
            ->line('Vos avis aident les autres parents à faire le bon choix et contribuent à améliorer la qualité des services sur notre plateforme.')
            ->action('Découvrir d\'autres babysitters', route('announcements.index'))
            ->line('Merci de contribuer à la communauté Miminoo !')
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
            //
        ];
    }
}
