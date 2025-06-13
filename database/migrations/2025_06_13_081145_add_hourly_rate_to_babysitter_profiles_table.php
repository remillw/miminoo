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
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('available_radius_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('babysitter_profiles', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
    }
};
