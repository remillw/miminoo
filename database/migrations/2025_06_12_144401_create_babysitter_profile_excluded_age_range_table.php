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
        Schema::create('babysitter_profile_excluded_age_range', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('babysitter_profile_id');
            $table->unsignedBigInteger('age_range_id');
            $table->timestamps();

            // Index unique pour éviter les doublons
            $table->unique(['babysitter_profile_id', 'age_range_id'], 'bp_excluded_age_unique');
            
            // Clés étrangères avec noms personnalisés
            $table->foreign('babysitter_profile_id', 'bp_excl_age_bp_fk')
                  ->references('id')->on('babysitter_profiles')->onDelete('cascade');
            $table->foreign('age_range_id', 'bp_excl_age_ar_fk')
                  ->references('id')->on('age_ranges')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('babysitter_profile_excluded_age_range');
    }
};
