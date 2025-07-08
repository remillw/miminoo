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
        // Modifier l'ENUM pour ajouter 'cancelled' et 'refunded'
        DB::statement("ALTER TABLE reservations MODIFY funds_status ENUM(
            'pending_service', 
            'held_for_validation', 
            'released', 
            'disputed', 
            'refunded',
            'cancelled'
        ) DEFAULT 'pending_service'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retourner à l'ENUM précédent
        DB::statement("ALTER TABLE reservations MODIFY funds_status ENUM(
            'pending_service', 
            'held_for_validation', 
            'released', 
            'disputed', 
            'refunded'
        ) DEFAULT 'pending_service'");
    }
};
