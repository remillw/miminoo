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
        // Modifier l'ENUM pour inclure les nouveaux statuts dans ads
        DB::statement("ALTER TABLE ads MODIFY COLUMN status ENUM('active', 'awaiting_payment', 'booked', 'service_completed', 'completed', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");
        
        // Modifier l'ENUM pour inclure les nouveaux statuts dans reservations
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending_payment', 'paid', 'active', 'service_completed', 'completed', 'cancelled_by_parent', 'cancelled_by_babysitter', 'disputed') NOT NULL DEFAULT 'pending_payment'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer l'ENUM original (attention: peut causer des erreurs si des données utilisent les nouveaux statuts)
        DB::statement("ALTER TABLE ads MODIFY COLUMN status ENUM('active', 'awaiting_payment', 'booked', 'completed', 'cancelled') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending_payment', 'paid', 'active', 'completed', 'cancelled_by_parent', 'cancelled_by_babysitter', 'disputed') NOT NULL DEFAULT 'pending_payment'");
    }
}; 