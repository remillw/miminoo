<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pour MySQL, on doit utiliser une requête SQL directe pour modifier un enum
        DB::statement("ALTER TABLE users MODIFY notification_provider ENUM('native', 'onesignal', 'expo') DEFAULT 'native'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retourner à l'ancien enum sans 'expo'
        DB::statement("ALTER TABLE users MODIFY notification_provider ENUM('native', 'onesignal') DEFAULT 'native'");
    }
};
