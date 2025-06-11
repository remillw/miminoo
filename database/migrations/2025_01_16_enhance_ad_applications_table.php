<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_applications', function (Blueprint $table) {
            // Nouveaux statuts pour les contre-offres
            $table->enum('status', ['pending', 'counter_offered', 'accepted', 'declined', 'expired'])
                  ->default('pending')
                  ->change();
            
            // Contre-offre du parent
            $table->decimal('counter_rate', 8, 2)->nullable()->after('proposed_rate');
            $table->text('counter_message')->nullable()->after('counter_rate');
            
            // Expiration automatique (24h)
            $table->timestamp('expires_at')->nullable()->after('counter_message');
            
            // Métadonnées utiles
            $table->timestamp('accepted_at')->nullable()->after('expires_at');
            $table->timestamp('viewed_at')->nullable()->after('accepted_at'); // Quand le parent a vu la candidature
        });
    }

    public function down(): void
    {
        Schema::table('ad_applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'declined'])
                  ->default('pending')
                  ->change();
                  
            $table->dropColumn([
                'counter_rate',
                'counter_message', 
                'expires_at',
                'accepted_at',
                'viewed_at'
            ]);
        });
    }
}; 