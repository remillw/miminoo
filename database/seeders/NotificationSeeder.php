<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer quelques utilisateurs pour les tests
        $users = User::take(3)->get();
        
        foreach ($users as $user) {
            // Notification de demande d'avis
            $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\ReviewRequestNotification',
                'data' => [
                    'title' => 'Demande d\'avis pour votre dernière garde',
                    'message' => 'Votre service s\'est bien passé ? Laissez un avis !',
                    'reservation_id' => 1
                ],
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30)
            ]);
            
            // Notification de fonds libérés (pour les babysitters)
            if ($user->hasRole('babysitter')) {
                $user->notifications()->create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'App\\Notifications\\FundsReleasedNotification',
                    'data' => [
                        'title' => 'Vos fonds ont été libérés',
                        'message' => 'Le paiement de 45€ a été transféré sur votre compte.',
                        'amount' => 45.00
                    ],
                    'created_at' => now()->subHour(),
                    'updated_at' => now()->subHour()
                ]);
            }
            
            // Notification générale
            $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\GeneralNotification',
                'data' => [
                    'title' => 'Nouvelle candidature pour votre annonce',
                    'message' => 'Une babysitter a postulé pour votre garde du weekend.',
                ],
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2)
            ]);
        }
    }
}
