<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Console\Command;

class ShowSyncStatus extends Command
{
    protected $signature = 'sync:status';
    protected $description = 'Affiche le statut de la synchronisation avec le SaaS';

    public function handle()
    {
        $this->info("📊 Statut de la synchronisation SaaS");
        $this->newLine();

        // Statistiques générales
        $totalArticles = Article::count();
        $saasArticles = Article::where('source', 'saas')->count();
        $webhookArticles = Article::where('source', 'webhook')->count();
        $publishedArticles = Article::where('status', 'published')->count();
        $totalCategories = Category::count();

        $this->table(['Métrique', 'Valeur'], [
            ['Total Articles', $totalArticles],
            ['Articles depuis SaaS', $saasArticles],
            ['Articles via Webhook', $webhookArticles],
            ['Articles Publiés', $publishedArticles],
            ['Total Catégories', $totalCategories],
        ]);

        // Dernière synchronisation
        $lastSyncArticle = Article::where('source', 'saas')
            ->whereNotNull('webhook_received_at')
            ->orderBy('webhook_received_at', 'desc')
            ->first();

        if ($lastSyncArticle) {
            $this->newLine();
            $this->info("🕒 Dernière synchronisation: " . $lastSyncArticle->webhook_received_at->format('d/m/Y H:i:s'));
        } else {
            $this->newLine();
            $this->warn("⚠️  Aucune synchronisation effectuée");
        }

        // Articles récents depuis le SaaS
        if ($saasArticles > 0) {
            $this->newLine();
            $this->info("📄 Derniers articles synchronisés depuis le SaaS:");
            
            $recentArticles = Article::where('source', 'saas')
                ->with('categories')
                ->orderBy('webhook_received_at', 'desc')
                ->take(5)
                ->get();

            $tableData = [];
            foreach ($recentArticles as $article) {
                $tableData[] = [
                    'ID' => $article->id,
                    'External ID' => $article->external_id,
                    'Titre' => substr($article->title, 0, 30) . '...',
                    'Status' => $article->status,
                    'Catégories' => $article->categories->pluck('name')->join(', '),
                    'Synchronisé le' => $article->webhook_received_at->format('d/m H:i')
                ];
            }

            $this->table(['ID', 'External ID', 'Titre', 'Status', 'Catégories', 'Synchronisé le'], $tableData);
        }

        // Catégories
        if ($totalCategories > 0) {
            $this->newLine();
            $this->info("🏷️  Catégories disponibles:");
            
            $categories = Category::withCount('articles')->get();
            $categoryData = [];
            foreach ($categories as $category) {
                $categoryData[] = [
                    'ID' => $category->id,
                    'Nom' => $category->name,
                    'Slug' => $category->slug,
                    'Articles' => $category->articles_count,
                ];
            }

            $this->table(['ID', 'Nom', 'Slug', 'Articles'], $categoryData);
        }

        $this->newLine();
        $this->info("💡 Commandes disponibles:");
        $this->line("  • php artisan sync:from-saas                         - Synchronisation incrémentale des articles");
        $this->line("  • php artisan sync:from-saas --sync-categories       - Synchroniser articles ET catégories");
        $this->line("  • php artisan sync:from-saas --categories-only       - Synchroniser seulement les catégories");
        $this->line("  • php artisan sync:from-saas --force                 - Synchronisation complète");
        $this->line("  • php artisan sync:from-saas --dry-run               - Test sans modification");
        $this->line("  • php artisan sync:from-saas --status=published      - Seulement les articles publiés");

        return 0;
    }
} 