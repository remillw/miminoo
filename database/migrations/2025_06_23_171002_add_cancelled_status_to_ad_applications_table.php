<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_applications', function (Blueprint $table) {
            // Ajouter le statut 'cancelled' à l'enum existant
            $table->enum('status', ['pending', 'counter_offered', 'accepted', 'declined', 'expired', 'cancelled'])
                  ->default('pending')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('ad_applications', function (Blueprint $table) {
            // Revenir à l'enum sans 'cancelled'
            $table->enum('status', ['pending', 'counter_offered', 'accepted', 'declined', 'expired'])
                  ->default('pending')
                  ->change();
        });
    }
}; 