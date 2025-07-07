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
            // Index composite pour les requêtes de statut + date
            $table->index(['status', 'date_start'], 'ads_status_date_start_index');
            $table->index(['status', 'service_completed_at'], 'ads_status_service_completed_index');
            
            // Index pour les timestamps de statut
            $table->index('status_updated_at');
            $table->index('expired_at');
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Index composite pour les requêtes de statut + date de fin
            $table->index(['status', 'service_end_at'], 'reservations_status_service_end_index');
            $table->index(['status', 'service_completed_at'], 'reservations_status_completed_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('ads_status_date_start_index');
            $table->dropIndex('ads_status_service_completed_index');
            $table->dropIndex(['status_updated_at']);
            $table->dropIndex(['expired_at']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_status_service_end_index');
            $table->dropIndex('reservations_status_completed_index');
        });
    }
};
