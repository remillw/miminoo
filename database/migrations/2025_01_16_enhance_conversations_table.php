<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Lien vers la candidature acceptée
            $table->foreignId('application_id')->nullable()->after('ad_id')->constrained('ad_applications')->onDelete('cascade');
            
            // Statut de la conversation
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active')->after('application_id');
            
            // Paiement et service
            $table->boolean('deposit_paid')->default(false)->after('status');
            $table->timestamp('service_started_at')->nullable()->after('deposit_paid');
            $table->timestamp('service_completed_at')->nullable()->after('service_started_at');
            
            // Métadonnées chat
            $table->timestamp('last_message_at')->nullable()->after('service_completed_at');
            $table->foreignId('last_message_by')->nullable()->after('last_message_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropForeign(['last_message_by']);
            
            $table->dropColumn([
                'application_id',
                'status',
                'deposit_paid',
                'service_started_at',
                'service_completed_at',
                'last_message_at',
                'last_message_by'
            ]);
        });
    }
}; 