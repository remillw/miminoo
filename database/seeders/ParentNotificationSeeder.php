<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParentNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer Manon (parent)
        $parent = User::find(4);
        
        if (!$parent) {
            $this->command->info('Parent avec ID 4 non trouvé');
            return;
        }

        // Supprimer les anciennes notifications de test
        DB::table('notifications')->where('notifiable_id', $parent->id)->delete();

        // Créer des notifications de test pour le parent
        $notifications = [
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\NewApplication',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $parent->id,
                'data' => json_encode([
                    'title' => 'Nouvelle candidature pour votre annonce',
                    'message' => 'Une babysitter a postulé pour votre garde du 15 janvier',
                    'application_id' => 1,
                    'announcement_title' => 'Garde occasionnelle'
                ]),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\NewMessage',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $parent->id,
                'data' => json_encode([
                    'title' => 'Nouveau message de Camille',
                    'message' => 'Camille vous a envoyé un message concernant votre annonce',
                    'conversation_id' => 1,
                    'sender_name' => 'Camille D.'
                ]),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ]
        ];

        foreach ($notifications as $notification) {
            DB::table('notifications')->insert($notification);
        }

        $this->command->info('Notifications de test créées pour le parent');
    }
} 