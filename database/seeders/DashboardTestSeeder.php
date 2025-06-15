<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DashboardTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer un babysitter existant (ID 3 - REMI BOUVANT)
        $babysitter = User::find(3);
        
        if (!$babysitter) {
            $this->command->info('Babysitter avec ID 3 non trouvé');
            return;
        }

        // Récupérer quelques annonces existantes
        $ads = Ad::where('status', 'active')->take(3)->get();
        
        if ($ads->isEmpty()) {
            $this->command->info('Aucune annonce active trouvée');
            return;
        }

        // Créer des candidatures de test avec différents statuts
        $statuses = ['pending', 'accepted', 'declined'];
        
        foreach ($ads as $index => $ad) {
            AdApplication::create([
                'ad_id' => $ad->id,
                'babysitter_id' => $babysitter->id,
                'motivation_note' => 'Je suis très motivé(e) pour cette garde !',
                'proposed_rate' => 15.00 + $index,
                'status' => $statuses[$index % 3],
                'expires_at' => now()->addHours(24),
            ]);
        }

        $this->command->info('Candidatures de test créées pour le dashboard babysitter');
    }
}
