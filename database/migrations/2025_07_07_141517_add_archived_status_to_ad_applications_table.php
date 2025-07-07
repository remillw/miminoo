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
        // Modifier l'enum pour ajouter 'archived'
        DB::statement("ALTER TABLE ad_applications MODIFY COLUMN status ENUM('pending','counter_offered','accepted','declined','expired','cancelled','archived') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir à l'enum original sans 'archived'
        DB::statement("ALTER TABLE ad_applications MODIFY COLUMN status ENUM('pending','counter_offered','accepted','declined','expired','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
