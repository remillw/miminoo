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
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_token')->nullable()->after('language');
            $table->enum('device_type', ['ios', 'android', 'web'])->nullable()->after('device_token');
            $table->timestamp('device_token_updated_at')->nullable()->after('device_type');
            
            // Index pour optimiser les requêtes de notifications push
            $table->index(['device_token', 'push_notifications']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['device_token', 'push_notifications']);
            $table->dropColumn(['device_token', 'device_type', 'device_token_updated_at']);
        });
    }
};
