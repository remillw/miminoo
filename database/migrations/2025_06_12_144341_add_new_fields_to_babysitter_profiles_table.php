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
            $table->boolean('is_available')->default(true)->after('available_radius_km');
            $table->boolean('has_driving_license')->default(false)->after('is_available');
            $table->boolean('has_vehicle')->default(false)->after('has_driving_license');
            $table->boolean('comfortable_with_all_ages')->default(false)->after('has_vehicle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('babysitter_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'is_available',
                'has_driving_license', 
                'has_vehicle',
                'comfortable_with_all_ages'
            ]);
        });
    }
};
