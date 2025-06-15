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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            
            // Relations principales
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained('ad_applications')->onDelete('cascade');
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('babysitter_id')->constrained('users')->onDelete('cascade');
            
            // Détails de la réservation
            $table->decimal('hourly_rate', 8, 2); // Tarif horaire final négocié
            $table->decimal('deposit_amount', 8, 2); // Montant de l'acompte (1h)
            $table->decimal('service_fee', 8, 2)->default(2.00); // Frais de service (2€)
            $table->decimal('total_deposit', 8, 2); // Total à payer (acompte + frais)
            
            // Statuts et dates
            $table->enum('status', [
                'pending_payment',    // En attente de paiement
                'paid',              // Payé, réservation confirmée
                'active',            // Service en cours
                'completed',         // Service terminé
                'cancelled_by_parent', // Annulé par le parent
                'cancelled_by_babysitter', // Annulé par la babysitter
                'disputed'           // En litige
            ])->default('pending_payment');
            
            $table->timestamp('reserved_at')->nullable(); // Date de réservation
            $table->timestamp('payment_due_at')->nullable(); // Date limite de paiement
            $table->timestamp('paid_at')->nullable(); // Date de paiement
            $table->timestamp('service_start_at')->nullable(); // Début du service
            $table->timestamp('service_end_at')->nullable(); // Fin du service
            $table->timestamp('cancelled_at')->nullable(); // Date d'annulation
            $table->timestamp('funds_released_at')->nullable(); // Date de libération des fonds
            
            // Informations d'annulation
            $table->enum('cancellation_reason', [
                'parent_unavailable',
                'babysitter_unavailable', 
                'emergency',
                'other'
            ])->nullable();
            $table->text('cancellation_note')->nullable();
            $table->boolean('cancellation_penalty')->default(false); // Pénalité appliquée
            
            // Stripe
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_transfer_id')->nullable(); // Transfer vers babysitter
            $table->json('stripe_metadata')->nullable();
            
            // Évaluations
            $table->boolean('parent_reviewed')->default(false);
            $table->boolean('babysitter_reviewed')->default(false);
            
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['status', 'created_at']);
            $table->index(['parent_id', 'status']);
            $table->index(['babysitter_id', 'status']);
            $table->index('payment_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
