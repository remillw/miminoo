<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdentityVerificationFailedNotification extends Notification
{
    use Queueable;

    protected $error;

    /**
     * Create a new notification instance.
     */
    public function __construct($error = null)
    {
        $this->error = $error;
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
        $message = (new MailMessage)
            ->subject('Vérification d\'identité à reprendre')
            ->greeting('Bonjour,')
            ->line('La vérification de votre identité n\'a pas pu être complétée.')
            ->line('Pour continuer à utiliser nos services de paiement, nous vous invitons à recommencer la vérification.')
            ->action('Recommencer la vérification', route('babysitter.identity-verification'));

        if ($this->error) {
            $message->line('Détail de l\'erreur : ' . (is_object($this->error) ? $this->error->code ?? 'Erreur inconnue' : $this->error));
        }

        return $message->line('Notre équipe reste à votre disposition pour vous aider.');
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
