<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveCompletedConversations;
use Illuminate\Console\Command;

class ArchiveOldConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:archive-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive les conversations et candidatures des gardes terminées depuis plus de 48h';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗂️ Lancement de l\'archivage automatique des conversations...');
        
        // Dispatch le job
        ArchiveCompletedConversations::dispatch();
        
        $this->info('✅ Job d\'archivage lancé avec succès');
        
        return Command::SUCCESS;
    }
} 