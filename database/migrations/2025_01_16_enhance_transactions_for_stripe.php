<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Types de transactions
            $table->enum('type', ['deposit', 'final_payment', 'refund', 'payout'])->after('babysitter_id');
            
            // Lien vers la candidature/conversation
            $table->foreignId('application_id')->nullable()->after('type')->constrained('ad_applications');
            $table->foreignId('conversation_id')->nullable()->after('application_id')->constrained('conversations');
            
            // Stripe Connect
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_id');
            $table->string('stripe_transfer_id')->nullable()->after('stripe_payment_intent_id'); // Transfer vers babysitter
            $table->string('destination_account_id')->nullable()->after('stripe_transfer_id'); // Compte Stripe de la babysitter
            
            // Répartition
            $table->decimal('babysitter_amount', 8, 2)->after('amount'); // Montant pour la babysitter (sans frais)
            $table->decimal('platform_fee', 8, 2)->default(2.00)->after('babysitter_amount'); // Frais plateforme
            
            // États avancés
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded'])
                  ->default('pending')
                  ->change();
            
            // Métadonnées
            $table->timestamp('processed_at')->nullable()->after('status');
            $table->text('failure_reason')->nullable()->after('processed_at');
            $table->json('stripe_metadata')->nullable()->after('failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropForeign(['conversation_id']);
            
            $table->dropColumn([
                'type',
                'application_id',
                'conversation_id',
                'stripe_payment_intent_id',
                'stripe_transfer_id', 
                'destination_account_id',
                'babysitter_amount',
                'platform_fee',
                'processed_at',
                'failure_reason',
                'stripe_metadata'
            ]);
            
            $table->enum('status', ['pending', 'succeeded', 'failed'])
                  ->default('pending')
                  ->change();
        });
    }
}; 