<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FundsReleasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Reservation $reservation)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vos fonds ont été libérés !')
            ->greeting('Bonjour ' . $notifiable->firstname . ' !')
            ->line('Les fonds de votre service de babysitting ont été libérés avec succès.')
            ->line('Montant : ' . $this->reservation->babysitter_amount . '€')
            ->line('Les fonds seront disponibles sur votre compte selon le calendrier de paiement Stripe.')
            ->action('Voir mes paiements', route('babysitter.payments'))
            ->line('Merci pour votre excellent service !');
    }

    public function toArray($notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'amount' => $this->reservation->babysitter_amount,
            'message' => 'Vos fonds ont été libérés',
            'type' => 'funds_released'
        ];
    }
} 