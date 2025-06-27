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
        Schema::table('ads', function (Blueprint $table) {
            // Champs pour les annonces créées par des guests (non connectés)
            $table->string('guest_email')->nullable()->after('user_id');
            $table->string('guest_token')->nullable()->unique()->after('guest_email');
            $table->timestamp('guest_expires_at')->nullable()->after('guest_token');
            $table->boolean('is_guest')->default(false)->after('guest_expires_at');
            
            // Modifier user_id pour accepter NULL (pour les annonces guests)
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['guest_email', 'guest_token', 'guest_expires_at', 'is_guest']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
