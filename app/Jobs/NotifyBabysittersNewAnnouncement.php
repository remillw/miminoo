<?php

namespace App\Jobs;

use App\Models\Ad;
use App\Services\AnnouncementNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyBabysittersNewAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Ad $ad
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Job notification babysitters démarré', ['ad_id' => $this->ad->id]);
            
            // S'assurer que l'adresse est chargée
            $this->ad->load('address');
            
            $notificationService = new AnnouncementNotificationService();
            $notificationService->notifyBabysittersInRadius($this->ad);
            
            Log::info('Job notification babysitters terminé', ['ad_id' => $this->ad->id]);
        } catch (\Exception $e) {
            Log::error('❌ Erreur dans job notification babysitters', [
                'ad_id' => $this->ad->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Marquer le job comme échoué pour retry
            throw $e;
        }
    }

    /**
     * Définir le nombre de tentatives
     */
    public $tries = 3;

    /**
     * Délai entre les tentatives
     */
    public $backoff = [30, 60, 120]; // 30s, 1min, 2min
}
