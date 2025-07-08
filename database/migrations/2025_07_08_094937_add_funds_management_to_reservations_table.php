<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Gestion des fonds et déblocage différé
            if (!Schema::hasColumn('reservations', 'funds_status')) {
                $table->enum('funds_status', [
                    'pending_service',      // Service pas encore commencé
                    'held_for_validation',  // Service terminé, fonds en attente de validation (24h)
                    'released',             // Fonds libérés vers la babysitter
                    'disputed',             // Réclamation en cours
                    'refunded'              // Fonds remboursés au parent
                ])->default('pending_service')->after('status');
            }
            
            // Timestamps de déblocage
            if (!Schema::hasColumn('reservations', 'funds_hold_until')) {
                $table->timestamp('funds_hold_until')->nullable()->after('funds_status');
            }
            
            // Les champs stripe_transfer_id, funds_released_at existent déjà
            
            // Montants calculés et stockés seulement si ils n'existent pas
            if (!Schema::hasColumn('reservations', 'babysitter_amount')) {
                $table->decimal('babysitter_amount', 8, 2)->nullable()->after('stripe_transfer_id');
            }
            if (!Schema::hasColumn('reservations', 'platform_fee')) {
                $table->decimal('platform_fee', 8, 2)->nullable()->after('babysitter_amount');
            }
            if (!Schema::hasColumn('reservations', 'stripe_fee')) {
                $table->decimal('stripe_fee', 8, 2)->nullable()->after('platform_fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'funds_status',
                'funds_hold_until',
                // funds_released_at et stripe_transfer_id ne sont pas supprimés car ils existaient avant
                'babysitter_amount',
                'platform_fee',
                'stripe_fee'
            ]);
        });
    }
};
