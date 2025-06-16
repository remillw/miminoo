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
            if (!Schema::hasColumn('babysitter_profiles', 'profile_photos')) {
                $table->json('profile_photos')->nullable()->after('comfortable_with_all_ages');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('babysitter_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('babysitter_profiles', 'profile_photos')) {
                $table->dropColumn('profile_photos');
            }
        });
    }
};
