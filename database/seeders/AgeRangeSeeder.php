<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgeRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ageRanges = [
            [
                'name' => '0-6 mois',
                'min_age_months' => 0,
                'max_age_months' => 6,
                'display_order' => 1,
                'is_active' => true
            ],
            [
                'name' => '6-12 mois',
                'min_age_months' => 6,
                'max_age_months' => 12,
                'display_order' => 2,
                'is_active' => true
            ],
            [
                'name' => '1-2 ans',
                'min_age_months' => 12,
                'max_age_months' => 24,
                'display_order' => 3,
                'is_active' => true
            ],
            [
                'name' => '2-3 ans',
                'min_age_months' => 24,
                'max_age_months' => 36,
                'display_order' => 4,
                'is_active' => true
            ],
            [
                'name' => '3-6 ans',
                'min_age_months' => 36,
                'max_age_months' => 72,
                'display_order' => 5,
                'is_active' => true
            ],
            [
                'name' => '6-10 ans',
                'min_age_months' => 72,
                'max_age_months' => 120,
                'display_order' => 6,
                'is_active' => true
            ],
            [
                'name' => '10-14 ans',
                'min_age_months' => 120,
                'max_age_months' => 168,
                'display_order' => 7,
                'is_active' => true
            ],
            [
                'name' => '14+ ans',
                'min_age_months' => 168,
                'max_age_months' => null, // Pas de limite supérieure
                'display_order' => 8,
                'is_active' => true
            ]
        ];

        foreach ($ageRanges as $ageRange) {
            DB::table('age_ranges')->updateOrInsert(
                ['name' => $ageRange['name']],
                array_merge($ageRange, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
