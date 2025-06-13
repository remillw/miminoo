<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAnnouncementInRadius extends Notification implements ShouldQueue
{
    use Queueable;

    protected $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle annonce dans votre rayon')
            ->greeting('Bonjour ' . $notifiable->firstname . ' !')
            ->line('Une nouvelle annonce correspondant à vos critères a été publiée.')
            ->line('Titre : ' . $this->announcement->title)
            ->line('Lieu : ' . $this->announcement->location)
            ->line('Date : ' . $this->announcement->date)
            ->action('Voir l\'annonce', route('announcements.show', $this->announcement->id))
            ->line('Merci d\'utiliser notre plateforme !');
    }

    public function toArray($notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'message' => 'Nouvelle annonce dans votre rayon'
        ];
    }
} 