<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\Reservation;
use App\Notifications\ReviewRequestNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateAnnouncementStatuses extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'announcements:update-statuses';

    /**
     * The console command description.
     */
    protected $description = 'Met à jour automatiquement les statuts des annonces selon leur état';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Mise à jour des statuts...');
        
        $now = Carbon::now();
        $totalUpdated = 0;
        
        // 1. Mise à jour en lot : expirer les annonces actives sans réservation dont la date est passée
        $expiredCount = Ad::where('status', 'active')
            ->where('date_start', '<', $now)
            ->whereDoesntHave('reservations', function($query) {
                $query->whereIn('status', ['pending', 'confirmed', 'paid']);
            })
            ->update([
                'status' => 'expired',
                'expired_at' => $now,
                'status_updated_at' => $now
            ]);
            
        if ($expiredCount > 0) {
            $this->info("✅ {$expiredCount} annonce(s) expirée(s)");
            $totalUpdated += $expiredCount;
        }
        
        // 2. Mise à jour en lot : marquer comme réservées les annonces avec réservations payées
        $bookedCount = Ad::where('status', 'active')
            ->whereHas('reservations', function($query) {
                $query->where('status', 'paid');
            })
            ->update([
                'status' => 'booked',
                'status_updated_at' => $now
            ]);
            
        if ($bookedCount > 0) {
            $this->info("✅ {$bookedCount} annonce(s) réservée(s)");
            $totalUpdated += $bookedCount;
        }
        
       
        // 4. Traiter les réservations avec service terminé (selon date/heure de fin)
        $completedReservations = Reservation::where('status', 'paid')
            ->where('service_end_at', '<', $now) // Service terminé selon la date/heure prévue
            ->whereNull('service_completed_at') // Pas encore marquées comme terminées
            ->with(['ad.parent', 'babysitter']) // Précharger les relations nécessaires
            ->limit(50) // Limiter pour éviter les timeouts
            ->get();
            
        foreach ($completedReservations as $reservation) {
            try {
                // Utiliser completeService() qui met automatiquement :
                // - status = 'service_completed'
                // - funds_status = 'held_for_validation' 
                // - funds_hold_until = now + 24h (pour libération automatique)
                $success = $reservation->completeService();
                
                if ($success) {
                    // Envoyer les notifications de demande d'avis
                    $this->sendReviewRequestNotifications($reservation);
                    
                    Log::info("Service terminé pour réservation #{$reservation->id} - fonds bloqués 24h");
                } else {
                    Log::warning("Échec completeService() pour réservation #{$reservation->id}");
                }
            } catch (\Exception $e) {
                Log::error("Erreur completeService() réservation #{$reservation->id}: " . $e->getMessage());
            }
        }
        
        if ($completedReservations->count() > 0) {
            $this->info("✅ {$completedReservations->count()} réservation(s) service terminé");
        }
        
        // 5. Mise à jour en lot : annonces service terminé (après 24h de blocage)
        $serviceCompletedCount = Ad::where('status', 'blocked_24h')
            ->whereHas('reservations', function($query) use ($now) {
                $query->where('status', 'paid')
                      ->where('service_end_at', '<', $now->copy()->subHours(24)); // Plus de 24h écoulées
            })
            ->update([
                'status' => 'service_completed',
                'service_completed_at' => $now,
                'status_updated_at' => $now
            ]);
            
        if ($serviceCompletedCount > 0) {
            $this->info("✅ {$serviceCompletedCount} annonce(s) service terminé (après déblocage des fonds)");
            $totalUpdated += $serviceCompletedCount;
        }
        
        // 6. Mise à jour en lot : finaliser les annonces anciennes
        $finalCompletedCount = Ad::where('status', 'service_completed')
            ->where('service_completed_at', '<', $now->copy()->subDays(7))
            ->update([
                'status' => 'completed',
                'status_updated_at' => $now
            ]);
            
        if ($finalCompletedCount > 0) {
            $this->info("✅ {$finalCompletedCount} annonce(s) finalisée(s)");
            $totalUpdated += $finalCompletedCount;
        }
        
        if ($totalUpdated === 0) {
            $this->info("ℹ️ Aucune mise à jour nécessaire");
        } else {
            $this->info("🎉 {$totalUpdated} mise(s) à jour effectuée(s)");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Envoie les notifications de demande d'avis
     */
    private function sendReviewRequestNotifications(Reservation $reservation)
    {
        try {
            // Vérifier que les relations nécessaires sont présentes
            if (!$reservation->ad || !$reservation->ad->parent) {
                Log::warning("Impossible d'envoyer notification parent - annonce ou parent manquant pour réservation #{$reservation->id}");
                return;
            }
            
            if (!$reservation->babysitter) {
                Log::warning("Impossible d'envoyer notification babysitter - babysitter manquante pour réservation #{$reservation->id}");
                return;
            }
            
            // Notification pour le parent (pour noter la babysitter)
            $reservation->ad->parent->notify(new ReviewRequestNotification(
                $reservation,
                'babysitter',
                'Votre garde d\'enfants est terminée ! Notez votre babysitter.'
            ));
            
            // Notification pour la babysitter (pour noter le parent)
            $reservation->babysitter->notify(new ReviewRequestNotification(
                $reservation,
                'parent',
                'Votre mission de babysitting est terminée ! Notez les parents.'
            ));
            
            Log::info("Notifications d'avis envoyées pour la réservation #{$reservation->id}");
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi des notifications d'avis pour la réservation #{$reservation->id}: " . $e->getMessage());
        }
    }
} 