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
            // Ajouter la référence à l'adresse
            $table->foreignId('address_id')->nullable()->after('description')->constrained('addresses')->onDelete('cascade');
            
            // Supprimer les anciens champs d'adresse (on les garde temporairement pour la migration des données)
            // On les supprimera dans une migration séparée après avoir migré les données
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropColumn('address_id');
        });
    }
};
