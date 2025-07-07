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
            // Rendre les champs plus flexibles pour le nouveau système
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('cascade')->after('ad_id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('babysitter_id');
            $table->string('type')->default('payment')->after('user_id'); // payment, refund, deduction
            $table->string('stripe_refund_id')->nullable()->after('stripe_id');
            $table->text('description')->nullable()->after('status');
            $table->json('metadata')->nullable()->after('description');
            
            // Modifier les contraintes existantes pour les rendre nullables
            $table->foreignId('ad_id')->nullable()->change();
            $table->foreignId('payer_id')->nullable()->change();
            $table->foreignId('babysitter_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['reservation_id', 'user_id', 'type', 'stripe_refund_id', 'description', 'metadata']);
            
            // Restaurer les contraintes non-nullables (attention : peut échouer si des données existent)
            $table->foreignId('ad_id')->nullable(false)->change();
            $table->foreignId('payer_id')->nullable(false)->change();
            $table->foreignId('babysitter_id')->nullable(false)->change();
        });
    }
}; 