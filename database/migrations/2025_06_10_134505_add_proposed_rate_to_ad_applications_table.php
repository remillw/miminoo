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
        Schema::table('ad_applications', function (Blueprint $table) {
            $table->decimal('proposed_rate', 8, 2)->nullable()->after('motivation_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_applications', function (Blueprint $table) {
            $table->dropColumn('proposed_rate');
        });
    }
};
