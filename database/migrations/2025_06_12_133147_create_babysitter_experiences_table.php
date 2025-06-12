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
        Schema::create('babysitter_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('babysitter_profile_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'formation' ou 'experience'
            $table->string('title'); // Titre de la formation/expérience
            $table->text('description')->nullable();
            $table->string('institution')->nullable(); // École, entreprise, etc.
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false); // En cours
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('babysitter_experiences');
    }
};
