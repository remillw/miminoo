<?php

namespace App\Jobs;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateCompletedReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = Carbon::now();
        
        // Trouver toutes les réservations qui sont terminées mais pas encore marquées comme "completed"
        $reservations = Reservation::where('status', 'paid')
            ->where('service_end_at', '<', $now)
            ->get();
        
        $updatedCount = 0;
        
        foreach ($reservations as $reservation) {
            $reservation->update(['status' => 'completed']);
            $updatedCount++;
            
            Log::info("Réservation #{$reservation->id} automatiquement mise à jour vers 'completed'", [
                'reservation_id' => $reservation->id,
                'parent_id' => $reservation->parent_id,
                'babysitter_id' => $reservation->babysitter_id,
                'service_end_at' => $reservation->service_end_at
            ]);
        }
        
        if ($updatedCount > 0) {
            Log::info("✅ {$updatedCount} réservation(s) automatiquement mise(s) à jour vers 'completed'");
        }
    }
}
