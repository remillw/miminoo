<?php

namespace App\Notifications;

use App\Models\AdApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplication extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected AdApplication $application)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $isParent = $notifiable->hasRole('parent');
        $babysitter = $this->application->babysitter;
        $announcement = $this->application->ad;

        if ($isParent) {
            return (new MailMessage)
                ->subject('Nouvelle candidature reçue !')
                ->greeting('Bonjour !')
                ->line("Vous avez reçu une nouvelle candidature de {$babysitter->firstname} {$babysitter->lastname}")
                ->line("Pour l'annonce : {$announcement->title}")
                ->action('Voir la candidature', route('messaging.index'))
                ->line('Ne tardez pas à répondre !');
        } else {
            return (new MailMessage)
                ->subject('Votre candidature a été envoyée !')
                ->greeting('Bonjour !')
                ->line("Votre candidature pour l'annonce \"{$announcement->title}\" a été envoyée avec succès.")
                ->action('Voir l\'annonce', route('announcements.show', $announcement))
                ->line('Le parent sera notifié et pourra vous répondre rapidement.');
        }
    }

    public function toArray($notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'announcement_title' => $this->application->ad->title,
            'message' => 'Nouvelle candidature'
        ];
    }
} 