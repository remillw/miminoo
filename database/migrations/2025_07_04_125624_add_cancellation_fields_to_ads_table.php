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
        Schema::table('ads', function (Blueprint $table) {
            // Ajouter le statut 'cancelled' aux options existantes
            $table->enum('status', ['active', 'awaiting_payment', 'booked', 'completed', 'cancelled'])
                  ->default('active')
                  ->change();
            
            // Colonnes pour l'annulation
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->enum('cancellation_reason', [
                'found_other_solution',
                'no_longer_needed', 
                'date_changed',
                'budget_issues',
                'other'
            ])->nullable()->after('cancelled_at');
            $table->text('cancellation_note')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Revenir à l'enum original
            $table->enum('status', ['active', 'awaiting_payment', 'booked', 'completed'])
                  ->default('active')
                  ->change();
            
            // Supprimer les colonnes d'annulation
            $table->dropColumn(['cancelled_at', 'cancellation_reason', 'cancellation_note']);
        });
    }
};
