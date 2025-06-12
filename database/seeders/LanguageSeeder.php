<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['name' => 'Français', 'code' => 'fr', 'is_active' => true],
            ['name' => 'Anglais', 'code' => 'en', 'is_active' => true],
            ['name' => 'Espagnol', 'code' => 'es', 'is_active' => true],
            ['name' => 'Italien', 'code' => 'it', 'is_active' => true],
            ['name' => 'Allemand', 'code' => 'de', 'is_active' => true],
            ['name' => 'Portugais', 'code' => 'pt', 'is_active' => true],
            ['name' => 'Arabe', 'code' => 'ar', 'is_active' => true],
            ['name' => 'Chinois', 'code' => 'zh', 'is_active' => true],
            ['name' => 'Russe', 'code' => 'ru', 'is_active' => true],
            ['name' => 'Japonais', 'code' => 'ja', 'is_active' => true],
        ];

        foreach ($languages as $language) {
            DB::table('languages')->updateOrInsert(
                ['code' => $language['code']],
                array_merge($language, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
