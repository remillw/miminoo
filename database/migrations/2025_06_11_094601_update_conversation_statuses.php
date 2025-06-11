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
        Schema::table('conversations', function (Blueprint $table) {
            // Modifier l'enum pour inclure les nouveaux statuts
            $table->enum('status', [
                'pending', // En attente de décision du parent
                'payment_required', // Réservé, en attente de paiement
                'active', // Paiement effectué, conversation active
                'completed', // Service terminé
                'cancelled', // Annulé
                'archived' // Refusé, archivé (n'apparaît plus)
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Revenir aux anciens statuts
            $table->enum('status', ['active', 'completed', 'cancelled'])
                  ->default('active')->change();
        });
    }
};
