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
            ->view('emails.funds-released', [
                'notifiable' => $notifiable,
                'reservation' => $this->reservation
            ]);
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