<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\StripeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReleasePendingFunds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Créer une nouvelle instance du job.
     */
    public function __construct()
    {
        //
    }

    /**
     * Exécuter le job.
     * Libère les fonds pour toutes les réservations qui ont dépassé leur période de validation (24h)
     */
    public function handle(StripeService $stripeService): void
    {
        Log::info('🔄 Démarrage du job de libération des fonds');

        // Récupérer toutes les réservations dont les fonds sont en attente et dépassent la limite
        $reservationsToRelease = Reservation::where('funds_status', 'held_for_validation')
            ->where('funds_hold_until', '<=', now())
            ->whereNull('funds_released_at')  // Pas encore libérés
            ->with(['babysitter', 'disputes'])
            ->get();

        Log::info('📊 Réservations trouvées pour libération', [
            'count' => $reservationsToRelease->count()
        ]);

        $successCount = 0;
        $errorCount = 0;

        foreach ($reservationsToRelease as $reservation) {
            try {
                // Vérifier qu'il n'y a pas de dispute en cours
                $hasActiveDispute = $reservation->disputes()
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->exists();

                if ($hasActiveDispute) {
                    Log::info('⚠️ Libération suspendue - Dispute en cours', [
                        'reservation_id' => $reservation->id
                    ]);
                    
                    // Mettre à jour le statut pour indiquer la dispute
                    $stripeService->holdFundsForDispute($reservation);
                    continue;
                }

                // Libérer les fonds vers la babysitter
                $transfer = $stripeService->releaseFundsToBabysitter($reservation);
                
                Log::info('✅ Fonds libérés avec succès', [
                    'reservation_id' => $reservation->id,
                    'babysitter_id' => $reservation->babysitter_id,
                    'amount' => $reservation->babysitter_amount,
                    'transfer_id' => $transfer->id
                ]);

                $successCount++;

            } catch (\Exception $e) {
                Log::error('❌ Erreur lors de la libération des fonds', [
                    'reservation_id' => $reservation->id,
                    'babysitter_id' => $reservation->babysitter_id,
                    'error' => $e->getMessage()
                ]);

                $errorCount++;
            }
        }

        Log::info('🏁 Job de libération des fonds terminé', [
            'total_found' => $reservationsToRelease->count(),
            'success' => $successCount,
            'errors' => $errorCount
        ]);
    }

    /**
     * Nombre de tentatives avant échec
     */
    public $tries = 3;

    /**
     * Délai avant nouvelle tentative (en secondes)
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Gérer l'échec du job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('💥 Job ReleasePendingFunds a échoué définitivement', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
