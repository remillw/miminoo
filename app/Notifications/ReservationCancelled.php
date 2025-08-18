<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reservation $reservation,
        public string $cancelledBy, // 'parent' ou 'babysitter'
        public string $reason,
        public ?string $note = null
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
        $cancellerName = $this->cancelledBy === 'parent' 
            ? $this->reservation->parent->firstname . ' ' . $this->reservation->parent->lastname
            : $this->reservation->babysitter->firstname . ' ' . $this->reservation->babysitter->lastname;

        $isToParent = $notifiable->id === $this->reservation->parent_id;
        $refundAmount = $this->reservation->getRefundAmount();

        $mail = (new MailMessage)
            ->subject('❌ Réservation annulée - ' . $this->reservation->ad->title)
            ->greeting('Bonjour ' . $notifiable->firstname . ' !');

        if ($isToParent) {
            // Email au parent
            $mail->line('Votre réservation de garde d\'enfants a été annulée par **' . $cancellerName . '**.')
                 ->line('')
                 ->line('**📅 Détails de la réservation annulée :**')
                 ->line('• **Date :** ' . $this->reservation->service_start_at->format('d/m/Y à H:i'))
                 ->line('• **Durée :** ' . $this->reservation->service_start_at->format('H:i') . ' - ' . $this->reservation->service_end_at->format('H:i'))
                 ->line('• **Babysitter :** ' . $this->reservation->babysitter->firstname . ' ' . $this->reservation->babysitter->lastname)
                 ->line('')
                 ->line('💳 **Détail du paiement :**')
                 ->line('• **Acompte :** ' . $this->reservation->deposit_amount . '€')
                 ->line('• **Frais de service :** ' . $this->reservation->service_fee . '€')
                 ->line('• **Total payé :** ' . $this->reservation->total_deposit . '€');

            if ($refundAmount > 0) {
                $mail->line('')
                     ->line('💰 **Remboursement :** ' . $refundAmount . '€ sera automatiquement crédité sur votre moyen de paiement sous 5-10 jours ouvrés.')
                     ->line('*(Les frais de service ne sont pas remboursables)*');
            } else {
                $mail->line('')
                     ->line('⚠️ **Aucun remboursement** ne sera effectué car l\'annulation a eu lieu moins de 24h avant le début du service.');
            }
        } else {
            // Email à la babysitter
            $mail->line('La réservation pour laquelle vous aviez postulé a été annulée par **' . $cancellerName . '**.')
                 ->line('')
                 ->line('**📅 Détails de la réservation annulée :**')
                 ->line('• **Date :** ' . $this->reservation->service_start_at->format('d/m/Y à H:i'))
                 ->line('• **Durée :** ' . $this->reservation->service_start_at->format('H:i') . ' - ' . $this->reservation->service_end_at->format('H:i'))
                 ->line('• **Parent :** ' . $this->reservation->parent->firstname . ' ' . $this->reservation->parent->lastname)
                 ->line('• **Montant que vous auriez reçu :** ' . $this->reservation->babysitter_amount . '€');

            if ($this->cancelledBy === 'babysitter') {
                $mail->line('')
                     ->line('⚠️ **Attention :** Cette annulation pourra affecter votre réputation si elle a lieu moins de 48h avant le service.');
            }
        }

        if ($this->note) {
            $mail->line('')
                 ->line('**📝 Message :** ' . $this->note);
        }

        $mail->line('')
             ->line('N\'hésitez pas à consulter d\'autres annonces disponibles sur la plateforme.')
             ->action('Voir les annonces', route('announcements.index'))
             ->line('Merci de votre compréhension.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Réservation annulée par ' . $this->cancelledBy,
            'reservation_id' => $this->reservation->id,
            'cancelled_by' => $this->cancelledBy,
            'reason' => $this->reason,
            'refund_amount' => $this->reservation->getRefundAmount(),
            'service_date' => $this->reservation->service_start_at
        ];
    }
} 