<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Models\ParentProfile;
use App\Models\BabysitterProfile;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RoleSeeder;

class CreateBaseUsers extends Command
{
    protected $signature = 'users:create-base';
    protected $description = 'Crée un utilisateur parent et un utilisateur babysitter de base';

    public function handle()
    {
        
        if (Role::count() === 0) {
            $this->info('Création des rôles...');
            (new RoleSeeder())->run();
            $this->info('Rôles créés avec succès !');
        }

        // Récupération des rôles
        $parentRole = Role::where('name', 'parent')->first();
        $babysitterRole = Role::where('name', 'babysitter')->first();

        if (!$parentRole || !$babysitterRole) {
            $this->error('Erreur lors de la création des rôles.');
            return 1;
        }

        // Création de l'utilisateur parent
        $parentUser = User::create([
            'name' => 'Parent Test',
            'email' => 'parent@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $parentRole->id,
            'is_verified' => 1,
            'email_verified_at' => now(),
        ]);

        ParentProfile::create([
            'user_id' => $parentUser->id,
        ]);


        
        $babysitterUser = User::create([
            'name' => 'Babysitter Test',
            'email' => 'babysitter@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $babysitterRole->id,
            'is_verified' => 1,
            'email_verified_at' => now(),
        ]);

        BabysitterProfile::create([
            'user_id' => $babysitterUser->id,
        ]);

        $this->info('Utilisateurs créés avec succès !');
        $this->table(
        
            ['Type', 'Email', 'Mot de passe', 'Vérifié'],
            [
                ['Parent', 'parent@test.com', 'password123', 'Oui'],
                ['Babysitter', 'babysitter@test.com', 'password123', 'Oui'],
            ]
        );

        return 0;
    }
} 