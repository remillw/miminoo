<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Première étape : nettoyer les données existantes qui ont des statuts invalides
        // Convertir les statuts 'expired' existants vers 'cancelled_by_parent' temporairement
        DB::statement("UPDATE reservations SET status = 'cancelled_by_parent' WHERE status NOT IN (
            'pending_payment',
            'paid',
            'active',
            'completed',
            'cancelled_by_parent',
            'cancelled_by_babysitter',
            'disputed'
        )");

        // Deuxième étape : modifier l'enum pour ajouter 'expired'
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM(
            'pending_payment',
            'paid',
            'active',
            'completed',
            'cancelled_by_parent',
            'cancelled_by_babysitter',
            'disputed',
            'expired'
        ) DEFAULT 'pending_payment'");

        // Troisième étape : remettre les statuts 'expired' là où c'est approprié
        // (réservations annulées avec une raison de timeout de paiement)
        DB::statement("UPDATE reservations SET status = 'expired' WHERE 
            status = 'cancelled_by_parent' AND 
            cancellation_reason = 'payment_timeout'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Avant de supprimer le statut 'expired', convertir les enregistrements vers un statut valide
        DB::statement("UPDATE reservations SET status = 'cancelled_by_parent' WHERE status = 'expired'");

        // Remettre l'enum à son état original
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM(
            'pending_payment',
            'paid',
            'active',
            'completed',
            'cancelled_by_parent',
            'cancelled_by_babysitter',
            'disputed'
        ) DEFAULT 'pending_payment'");
    }
};
