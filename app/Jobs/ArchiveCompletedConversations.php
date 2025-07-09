<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveCompletedConversations implements ShouldQueue
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
        $archivedCount = 0;
        $now = Carbon::now();
        
        // Archiver les conversations liées à des réservations terminées depuis plus de 24h
        $conversationsToArchive = Conversation::where('archived_at', null)
            ->where('type', 'reservation')
            ->whereHas('reservation', function($query) use ($now) {
                $query->where('status', 'service_completed')
                      ->where('service_end_at', '<', $now->copy()->subHours(24));
            })
            ->with('reservation')
            ->get();

        foreach ($conversationsToArchive as $conversation) {
            try {
                $conversation->update([
                    'archived_at' => $now,
                    'status' => 'archived'
                ]);
                
                Log::info("Conversation #{$conversation->id} archivée pour réservation #{$conversation->reservation->id}");
                $archivedCount++;
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'archivage de la conversation #{$conversation->id}: " . $e->getMessage());
            }
        }

        // Archiver aussi les conversations de candidatures acceptées dont la réservation est terminée depuis 24h
        $applicationConversationsToArchive = Conversation::where('archived_at', null)
            ->where('type', 'application')
            ->whereHas('application', function($query) use ($now) {
                $query->where('status', 'accepted')
                      ->whereHas('ad', function($adQuery) use ($now) {
                          $adQuery->where('status', 'service_completed')
                                  ->whereHas('reservations', function($resQuery) use ($now) {
                                      $resQuery->where('status', 'service_completed')
                                              ->where('service_end_at', '<', $now->copy()->subHours(24));
                                  });
                      });
            })
            ->with(['application.ad.reservations'])
            ->get();

        foreach ($applicationConversationsToArchive as $conversation) {
            try {
                $conversation->update([
                    'archived_at' => $now,
                    'status' => 'archived'
                ]);
                
                Log::info("Conversation de candidature #{$conversation->id} archivée");
                $archivedCount++;
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'archivage de la conversation de candidature #{$conversation->id}: " . $e->getMessage());
            }
        }

        if ($archivedCount > 0) {
            Log::info("✅ {$archivedCount} conversation(s) archivée(s) automatiquement");
        } else {
            Log::info("ℹ️ Aucune conversation à archiver");
        }
    }
} 