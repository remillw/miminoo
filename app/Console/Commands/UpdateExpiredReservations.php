<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marquer automatiquement les réservations en attente de paiement comme expirées quand les dates sont passées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Vérification des réservations en attente de paiement expirées...');

        // Récupérer toutes les réservations en attente de paiement où :
        // 1. Le délai de paiement est dépassé (payment_due_at < now())
        // 2. OU la date de service est déjà passée (service_start_at < now())
        $expiredReservations = Reservation::where('status', 'pending_payment')
            ->where(function($query) {
                $query->where('payment_due_at', '<', now())
                      ->orWhere('service_start_at', '<', now());
            })
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('Aucune réservation expirée trouvée.');
            return;
        }

        $count = 0;
        foreach ($expiredReservations as $reservation) {
            // Marquer comme expirée
            $reservation->update([
                'status' => 'expired',
                'cancelled_at' => now(),
                'cancellation_reason' => 'payment_timeout',
                'cancellation_note' => 'Réservation expirée automatiquement - paiement non effectué dans les délais'
            ]);

            // Mettre à jour l'annonce pour la remettre en active si elle n'était que réservée
            if ($reservation->ad && $reservation->ad->status === 'booked') {
                $reservation->ad->update(['status' => 'active']);
            }

            // Archiver la conversation
            if ($reservation->conversation) {
                $reservation->conversation->update(['status' => 'archived']);
            }

            // Mettre l'application en archived aussi
            if ($reservation->application) {
                $reservation->application->update(['status' => 'archived']);
            }

            $count++;
            Log::info('Réservation expirée automatiquement', [
                'reservation_id' => $reservation->id,
                'parent_id' => $reservation->parent_id,
                'babysitter_id' => $reservation->babysitter_id,
                'service_start_at' => $reservation->service_start_at,
                'payment_due_at' => $reservation->payment_due_at
            ]);
        }

        $this->info("✅ {$count} réservation(s) marquée(s) comme expirée(s).");
        Log::info("{$count} réservations marquées comme expirées automatiquement");
    }
} 