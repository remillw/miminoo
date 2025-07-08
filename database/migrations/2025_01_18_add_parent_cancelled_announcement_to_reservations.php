<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter 'parent_cancelled_announcement' aux valeurs autorisées pour cancellation_reason
        DB::statement("ALTER TABLE reservations MODIFY COLUMN cancellation_reason ENUM('parent_unavailable', 'babysitter_unavailable', 'emergency', 'parent_cancelled_announcement', 'other') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour à l'enum original
        DB::statement("ALTER TABLE reservations MODIFY COLUMN cancellation_reason ENUM('parent_unavailable', 'babysitter_unavailable', 'emergency', 'other') NULL");
    }
}; 