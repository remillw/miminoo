<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateReservationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour automatiquement le statut des réservations terminées';

    /**
     * Execute the console command.
     */
    public function handle()
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
            
            $this->info("Réservation #{$reservation->id} mise à jour vers 'completed'");
        }
        
        if ($updatedCount > 0) {
            $this->info("✅ {$updatedCount} réservation(s) mise(s) à jour vers 'completed'");
        } else {
            $this->info("ℹ️  Aucune réservation à mettre à jour");
        }
        
        return Command::SUCCESS;
    }
}
