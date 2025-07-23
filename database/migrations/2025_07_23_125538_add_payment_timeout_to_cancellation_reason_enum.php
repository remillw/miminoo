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
        // D'abord, nettoyer les données existantes avec des valeurs invalides
        DB::statement("UPDATE reservations SET cancellation_reason = 'other' WHERE cancellation_reason NOT IN (
            'parent_unavailable',
            'babysitter_unavailable',
            'emergency',
            'other'
        ) AND cancellation_reason IS NOT NULL");

        // Modifier l'enum pour ajouter 'payment_timeout'
        DB::statement("ALTER TABLE reservations MODIFY COLUMN cancellation_reason ENUM(
            'parent_unavailable',
            'babysitter_unavailable',
            'emergency',
            'other',
            'payment_timeout'
        ) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre l'enum à son état original
        // D'abord, convertir les valeurs 'payment_timeout' vers 'other'
        DB::statement("UPDATE reservations SET cancellation_reason = 'other' WHERE cancellation_reason = 'payment_timeout'");
        
        // Puis remettre l'enum sans 'payment_timeout'
        DB::statement("ALTER TABLE reservations MODIFY COLUMN cancellation_reason ENUM(
            'parent_unavailable',
            'babysitter_unavailable',
            'emergency',
            'other'
        ) NULL");
    }
};
