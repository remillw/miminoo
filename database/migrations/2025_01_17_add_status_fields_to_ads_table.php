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
            // Horodatage pour suivre les transitions de statut
            $table->timestamp('status_updated_at')->nullable()->after('status');
            $table->timestamp('service_completed_at')->nullable()->after('status_updated_at');
            $table->timestamp('expired_at')->nullable()->after('service_completed_at');
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Ajouter le nouveau statut service_completed
            $table->timestamp('service_completed_at')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['status_updated_at', 'service_completed_at', 'expired_at']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('service_completed_at');
        });
    }
}; 