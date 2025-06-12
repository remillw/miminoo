<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            [
                'name' => 'Aide aux devoirs',
                'description' => 'Accompagnement scolaire et aide aux devoirs',
                'category' => 'éducation',
                'is_active' => true
            ],
            [
                'name' => 'Activités créatives',
                'description' => 'Bricolage, dessin, peinture, activités manuelles',
                'category' => 'loisirs',
                'is_active' => true
            ],
            [
                'name' => 'Premiers secours',
                'description' => 'Formation aux gestes de premiers secours',
                'category' => 'sécurité',
                'is_active' => true
            ],
            [
                'name' => 'Cuisine',
                'description' => 'Préparation de repas et goûters',
                'category' => 'vie quotidienne',
                'is_active' => true
            ],
            [
                'name' => 'Sport et activités physiques',
                'description' => 'Encadrement d\'activités sportives et jeux extérieurs',
                'category' => 'sport',
                'is_active' => true
            ],
            [
                'name' => 'Musique',
                'description' => 'Initiation musicale et chant',
                'category' => 'art',
                'is_active' => true
            ],
            [
                'name' => 'Lecture et contes',
                'description' => 'Lecture d\'histoires et animation de contes',
                'category' => 'éducation',
                'is_active' => true
            ],
            [
                'name' => 'Jeux éducatifs',
                'description' => 'Animation de jeux pédagogiques',
                'category' => 'éducation',
                'is_active' => true
            ],
            [
                'name' => 'Soins bébé',
                'description' => 'Change, biberon, soins aux nourrissons',
                'category' => 'soins',
                'is_active' => true
            ],
            [
                'name' => 'Gestion des conflits',
                'description' => 'Médiation et résolution de conflits entre enfants',
                'category' => 'éducation',
                'is_active' => true
            ]
        ];

        foreach ($skills as $skill) {
            DB::table('skills')->updateOrInsert(
                ['name' => $skill['name']],
                array_merge($skill, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
