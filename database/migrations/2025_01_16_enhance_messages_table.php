<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Types de messages
            $table->enum('type', ['text', 'image', 'system'])->default('text')->after('message');
            
            // Métadonnées
            $table->json('metadata')->nullable()->after('type'); // Pour images, liens, etc.
            
            // États de lecture et écriture
            $table->timestamp('delivered_at')->nullable()->after('read_at');
            $table->boolean('is_edited')->default(false)->after('delivered_at');
            $table->timestamp('edited_at')->nullable()->after('is_edited');
            
            // Pour les messages système (candidature acceptée, paiement effectué, etc.)
            $table->json('system_data')->nullable()->after('edited_at');
        });

        // Index pour les performances
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'created_at']);
            $table->dropIndex(['sender_id', 'created_at']);
            $table->dropIndex(['read_at']);
            
            $table->dropColumn([
                'type',
                'metadata',
                'delivered_at',
                'is_edited',
                'edited_at',
                'system_data'
            ]);
        });
    }
}; 