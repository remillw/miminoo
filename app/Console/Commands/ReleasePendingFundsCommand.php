<?php

namespace App\Console\Commands;

use App\Jobs\ReleasePendingFunds;
use Illuminate\Console\Command;

class ReleasePendingFundsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'funds:release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libère les fonds en attente vers les babysitters après la période de validation (24h)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Démarrage de la libération des fonds en attente...');
        
        // Dispatch the job directly
        ReleasePendingFunds::dispatch();
        
        $this->info('✅ Job de libération des fonds lancé avec succès');
        
        return Command::SUCCESS;
    }
}
