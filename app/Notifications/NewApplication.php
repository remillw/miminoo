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
                ->view('emails.new-application-parent', [
                    'parent' => $notifiable,
                    'babysitter' => $babysitter,
                    'announcement' => $announcement,
                    'application' => $this->application
                ]);
        } else {
            return (new MailMessage)
                ->subject('Votre candidature a été envoyée !')
                ->view('emails.new-application-babysitter', [
                    'babysitter' => $notifiable,
                    'parent' => $announcement->user,
                    'announcement' => $announcement,
                    'application' => $this->application
                ]);
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