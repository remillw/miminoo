<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'user',
                'label' => 'User',
            ],
            [
                'name' => 'parent',
                'label' => 'Parent',
            ],

            [
                'name' => 'babysitter',
                'label' => 'Babysitter',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
} 