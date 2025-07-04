<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reservation $reservation
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $parent = $this->reservation->parent;
        $ad = $this->reservation->ad;
        
        return (new MailMessage)
            ->subject('💳 Réservation payée - Service confirmé !')
            ->greeting('Bonne nouvelle ' . $notifiable->firstname . ' !')
            ->line('Le parent **' . $parent->firstname . ' ' . $parent->lastname . '** vient de payer l\'acompte pour votre garde.')
            ->line('')
            ->line('**📅 Détails de la réservation :**')
            ->line('• **Date :** ' . $ad->date_start->format('d/m/Y à H:i'))
            ->line('• **Durée :** ' . $ad->date_start->format('H:i') . ' - ' . $ad->date_end->format('H:i'))
            ->line('• **Tarif :** ' . $this->reservation->hourly_rate . '€/h')
            ->line('• **Acompte payé :** ' . $this->reservation->total_deposit . '€')
            ->line('• **Montant que vous recevrez :** ' . $this->reservation->babysitter_amount . '€')
            ->line('')
            ->line('💰 **Quand serez-vous payée ?**')
            ->line('Vos fonds seront automatiquement transférés sur votre compte **24h après la fin du service** (soit le ' . $ad->date_end->addDay()->format('d/m/Y à H:i') . ').')
            ->line('')
            ->line('📱 Vous pouvez dès maintenant discuter avec le parent via la messagerie pour finaliser les détails.')
            ->action('Voir la conversation', route('messaging.index'))
            ->line('Merci de faire confiance à ' . config('app.name') . ' !');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Réservation payée ! Service confirmé pour le ' . $this->reservation->ad->date_start->format('d/m/Y'),
            'reservation_id' => $this->reservation->id,
            'amount' => $this->reservation->babysitter_amount,
            'payment_date' => $this->reservation->paid_at,
            'service_date' => $this->reservation->ad->date_start
        ];
    }
} 