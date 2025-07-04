<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\AdApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        Log::info('🗂️ Début de l\'archivage automatique des conversations terminées');

        // Trouver les conversations des gardes terminées depuis plus de 48h
        $conversations = Conversation::with(['ad', 'application'])
            ->whereNotIn('status', ['archived', 'cancelled'])
            ->whereHas('ad', function($query) {
                $query->where('date_end', '<', Carbon::now()->subHours(48));
            })
            ->get();

        $archivedCount = 0;
        $archivedApplicationsCount = 0;

        foreach ($conversations as $conversation) {
            try {
                // Archiver la conversation
                $conversation->update(['status' => 'archived']);
                
                // Ajouter un message système
                $conversation->addSystemMessage('conversation_archived', [
                    'archived_by_name' => 'Système (automatique)',
                    'reason' => 'Garde terminée depuis 48h'
                ]);

                // Archiver la candidature associée si elle existe
                if ($conversation->application) {
                    $conversation->application->update(['status' => 'archived']);
                    $archivedApplicationsCount++;
                }

                $archivedCount++;

                Log::info('✅ Conversation archivée automatiquement', [
                    'conversation_id' => $conversation->id,
                    'ad_id' => $conversation->ad_id,
                    'ad_title' => $conversation->ad->title ?? 'N/A',
                    'ad_end_date' => $conversation->ad->date_end,
                    'hours_since_end' => Carbon::now()->diffInHours($conversation->ad->date_end)
                ]);

            } catch (\Exception $e) {
                Log::error('❌ Erreur lors de l\'archivage automatique d\'une conversation', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info('✅ Archivage automatique terminé', [
            'conversations_archivees' => $archivedCount,
            'candidatures_archivees' => $archivedApplicationsCount,
            'total_conversations_examinees' => $conversations->count()
        ]);
    }
} 