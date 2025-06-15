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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade'); // Qui fait la réclamation
            $table->foreignId('reported_id')->constrained('users')->onDelete('cascade'); // Contre qui
            
            $table->enum('reason', [
                'service_not_provided',
                'poor_service_quality',
                'late_arrival',
                'early_departure',
                'inappropriate_behavior',
                'payment_issue',
                'other'
            ]);
            
            $table->text('description');
            
            $table->enum('status', [
                'pending',
                'in_progress', 
                'resolved',
                'rejected'
            ])->default('pending');
            
            $table->text('admin_response')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['status', 'created_at']);
            $table->index(['reporter_id', 'status']);
            $table->index('reservation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
