<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrer d'abord les données existantes si nécessaire
        // Convertir 'text' en 'user' s'il y en a (mais il n'y en a pas d'après notre vérification)
        DB::table('messages')->where('type', 'text')->update(['type' => 'user']);
        
        Schema::table('messages', function (Blueprint $table) {
            // Changer l'enum type pour utiliser 'user' à la place de 'text'
            $table->enum('type', ['user', 'system', 'image'])->default('user')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Remettre l'ancien enum
            $table->enum('type', ['text', 'image', 'system'])->change();
        });
        
        // Reconvertir les données
        DB::table('messages')->where('type', 'user')->update(['type' => 'text']);
    }
};
