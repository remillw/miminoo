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
        Schema::create('age_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // "0-1 an", "1-3 ans", etc.
            $table->integer('min_age_months'); // âge minimum en mois
            $table->integer('max_age_months')->nullable(); // âge maximum en mois (null = pas de limite)
            $table->integer('display_order')->default(0); // pour l'ordre d'affichage
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('age_ranges');
    }
};
