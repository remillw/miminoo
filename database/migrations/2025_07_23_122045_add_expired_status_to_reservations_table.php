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
        // Modifier l'enum pour ajouter 'expired'
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM(
            'pending_payment',
            'paid',
            'active',
            'completed',
            'cancelled_by_parent',
            'cancelled_by_babysitter',
            'disputed',
            'expired'
        ) DEFAULT 'pending_payment'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre l'enum à son état original
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM(
            'pending_payment',
            'paid',
            'active',
            'completed',
            'cancelled_by_parent',
            'cancelled_by_babysitter',
            'disputed'
        ) DEFAULT 'pending_payment'");
    }
};
