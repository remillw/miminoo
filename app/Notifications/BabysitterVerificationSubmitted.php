<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BabysitterVerificationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Demande de vérification envoyée avec succès')
            ->markdown('emails.babysitter-verification-submitted', [
                'notifiable' => $notifiable
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Demande de vérification envoyée avec succès'
        ];
    }
} 