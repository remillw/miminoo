<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            
            // Données de base
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content'); // HTML reçu du webhook
            $table->text('excerpt')->nullable();
            $table->string('featured_image_url')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('og_image')->nullable();
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            
            // Statut et dates
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable();
            
            // Auteur
            $table->string('author_name')->nullable();
            $table->text('author_bio')->nullable();
            
            // Gestion des webhooks
            $table->string('external_id')->unique(); // ID depuis la plateforme SaaS
            $table->string('source')->default('webhook'); // Source de l'article
            $table->timestamp('webhook_received_at')->nullable();
            $table->json('webhook_data')->nullable(); // Données brutes du webhook
            
            // Métadonnées
            $table->boolean('is_featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('reading_time')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour les performances
            $table->index(['status', 'published_at']);
            $table->index('external_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
}; 