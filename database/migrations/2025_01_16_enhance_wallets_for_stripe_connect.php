<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Stripe Connect
            $table->string('stripe_account_id')->nullable()->after('user_id');
            $table->boolean('stripe_onboarding_completed')->default(false)->after('stripe_account_id');
            $table->json('stripe_capabilities')->nullable()->after('stripe_onboarding_completed'); // card_payments, transfers, etc.
            
            // Balance détaillée
            $table->decimal('pending_balance', 10, 2)->default(0)->after('balance'); // En attente de service
            $table->decimal('available_balance', 10, 2)->default(0)->after('pending_balance'); // Disponible pour retrait
            
            // Métadonnées
            $table->timestamp('last_payout_at')->nullable()->after('last_transfer_at');
            $table->decimal('total_earned', 10, 2)->default(0)->after('last_payout_at'); // Total gagné depuis le début
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_account_id',
                'stripe_onboarding_completed',
                'stripe_capabilities',
                'pending_balance',
                'available_balance', 
                'last_payout_at',
                'total_earned'
            ]);
        });
    }
}; 