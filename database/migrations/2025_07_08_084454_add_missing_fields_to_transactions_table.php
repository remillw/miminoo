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
        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter les colonnes nécessaires pour le système de remboursement
            if (!Schema::hasColumn('transactions', 'reservation_id')) {
                $table->foreignId('reservation_id')->nullable()->after('ad_id')->constrained('reservations')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('transactions', 'type')) {
                $table->enum('type', ['payment', 'refund', 'deduction', 'payout'])->default('payment')->after('babysitter_id');
            }
            
            if (!Schema::hasColumn('transactions', 'stripe_refund_id')) {
                $table->string('stripe_refund_id')->nullable()->after('stripe_id');
            }
            
            if (!Schema::hasColumn('transactions', 'description')) {
                $table->text('description')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('transactions', 'metadata')) {
                $table->json('metadata')->nullable()->after('description');
            }

            // Mettre à jour l'enum status pour inclure plus d'options
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded'])
                  ->default('pending')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'reservation_id')) {
                $table->dropForeign(['reservation_id']);
                $table->dropColumn('reservation_id');
            }
            
            if (Schema::hasColumn('transactions', 'type')) {
                $table->dropColumn('type');
            }
            
            if (Schema::hasColumn('transactions', 'stripe_refund_id')) {
                $table->dropColumn('stripe_refund_id');
            }
            
            if (Schema::hasColumn('transactions', 'description')) {
                $table->dropColumn('description');
            }
            
            if (Schema::hasColumn('transactions', 'metadata')) {
                $table->dropColumn('metadata');
            }

            // Restaurer l'ancien enum status
            $table->enum('status', ['pending', 'succeeded', 'failed'])
                  ->default('pending')
                  ->change();
        });
    }
};
