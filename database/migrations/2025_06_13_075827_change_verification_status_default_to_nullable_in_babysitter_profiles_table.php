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
        Schema::table('babysitter_profiles', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                  ->nullable()
                  ->default(null)
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('babysitter_profiles', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                  ->default('pending')
                  ->change();
        });
    }
};
