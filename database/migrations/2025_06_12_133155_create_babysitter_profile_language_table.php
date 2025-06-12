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
        Schema::create('babysitter_profile_language', function (Blueprint $table) {
            $table->id();
            $table->foreignId('babysitter_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('language_id')->constrained()->onDelete('cascade');
            $table->string('level')->nullable(); // débutant, intermédiaire, avancé, natif
            $table->timestamps();
            
            $table->unique(['babysitter_profile_id', 'language_id'], 'bp_language_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('babysitter_profile_language');
    }
};
