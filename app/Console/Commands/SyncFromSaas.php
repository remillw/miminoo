<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyncFromSaas extends Command
{
    protected $signature = 'sync:from-saas 
                           {--saas-url=http://localhost:8000} 
                           {--api-key= : Clé API pour authentification}
                           {--force : Forcer la synchronisation complète}
                           {--dry-run : Simuler sans insérer en base}
                           {--status= : Filtrer par status (draft, published)}
                           {--per-page=50 : Nombre d\'articles par page}
                           {--sync-categories : Synchroniser aussi les catégories}
                           {--categories-only : Synchroniser seulement les catégories}';
    
    protected $description = 'Synchronise les articles et catégories depuis le SaaS vers Laravel';

    private int $articlesCreated = 0;
    private int $articlesUpdated = 0;
    private int $categoriesCreated = 0;
    private int $categoriesUpdated = 0;
    private int $articlesSkipped = 0;

    public function handle()
    {
        $saasUrl = $this->option('saas-url');
        $apiKey = $this->option('api-key');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $status = $this->option('status');
        $perPage = (int) $this->option('per-page');
        $syncCategories = $this->option('sync-categories');
        $categoriesOnly = $this->option('categories-only');

        if (!$apiKey) {
            $apiKey = config('webhook.api_key');
            if (!$apiKey) {
                $this->error("❌ Clé API requise. Utilisez --api-key=votre-cle ou configurez WEBHOOK_API_KEY");
                return 1;
            }
        }

        $this->info("🔄 Synchronisation depuis le SaaS");
        $this->info("🌐 SaaS: {$saasUrl}");
        $this->info("🔑 API Key: " . substr($apiKey, 0, 8) . '...');
        
        if ($dryRun) {
            $this->warn("🧪 Mode DRY-RUN activé - Aucune modification en base");
        }

        if ($categoriesOnly) {
            $this->info("🏷️  Mode: Synchronisation des catégories uniquement");
        } elseif ($syncCategories) {
            $this->info("📄🏷️  Mode: Synchronisation des articles ET catégories");
        } else {
            $this->info("📄 Mode: Synchronisation des articles uniquement");
        }
        
        $this->newLine();

        try {
            // Synchroniser les catégories si demandé
            if ($syncCategories || $categoriesOnly) {
                $this->syncCategoriesFromSaas($saasUrl, $apiKey, $dryRun);
                $this->newLine();
            }

            // Synchroniser les articles si pas en mode categories-only
            if (!$categoriesOnly) {
                $this->syncArticlesFromSaas($saasUrl, $apiKey, $force, $dryRun, $status, $perPage);
            }

            // Afficher les résultats finaux
            $this->newLine();
            $this->info("📊 Résultats globaux de la synchronisation:");
            $this->table(['Métrique', 'Valeur'], [
                ['Articles créés', $this->articlesCreated],
                ['Articles mis à jour', $this->articlesUpdated],
                ['Articles ignorés', $this->articlesSkipped],
                ['Catégories créées', $this->categoriesCreated],
                ['Catégories mises à jour', $this->categoriesUpdated],
                ['Mode', $dryRun ? 'DRY-RUN' : 'RÉEL'],
            ]);

            $this->newLine();
            $this->info("✅ Synchronisation terminée avec succès !");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la synchronisation:");
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function syncCategoriesFromSaas(string $saasUrl, string $apiKey, bool $dryRun): void
    {
        $this->info("🏷️  Synchronisation des catégories...");

        // Faire la requête pour récupérer les catégories
        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
                'X-API-Key' => $apiKey,
            ])
            ->get($saasUrl . '/api/categories');

        if (!$response->successful()) {
            if ($response->status() === 404) {
                $this->warn("⚠️  Endpoint /api/categories non trouvé - Synchronisation des catégories ignorée");
                return;
            }
            throw new \Exception("Erreur lors de la récupération des catégories: " . $response->status());
        }

        $data = $response->json();
        
        // Gérer différents formats de réponse
        $categories = [];
        if (isset($data['categories'])) {
            $categories = $data['categories'];
        } elseif (isset($data['data'])) {
            $categories = $data['data'];
        } elseif (is_array($data) && isset($data[0])) {
            $categories = $data;
        } else {
            $this->warn("⚠️  Format de réponse des catégories non reconnu");
            return;
        }

        $this->info("🏷️  Catégories récupérées: " . count($categories));

        if (empty($categories)) {
            $this->info("✅ Aucune catégorie à synchroniser");
            return;
        }

        // Traiter les catégories
        $progressBar = $this->output->createProgressBar(count($categories));
        $progressBar->start();

        foreach ($categories as $categoryData) {
            if (!$dryRun) {
                $this->processCategoryFromSaas($categoryData);
            } else {
                // En mode dry-run, juste simuler
                $categoryName = $categoryData['name'] ?? $categoryData['title'] ?? null;
                if ($categoryName) {
                    $existingCategory = Category::where('name', $categoryName)->first();
                    if ($existingCategory) {
                        $this->categoriesUpdated++;
                    } else {
                        $this->categoriesCreated++;
                    }
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function processCategoryFromSaas(array $categoryData): void
    {
        try {
            DB::transaction(function () use ($categoryData) {
                $categoryName = $categoryData['name'] ?? $categoryData['title'] ?? null;
                
                if (empty($categoryName)) {
                    return;
                }

                // Chercher la catégorie existante
                $category = Category::where('name', $categoryName)->first();

                // Préparer les données
                $data = [
                    'name' => $categoryName,
                    'slug' => $categoryData['slug'] ?? Str::slug($categoryName),
                    'description' => $categoryData['description'] ?? null,
                    'color' => $categoryData['color'] ?? null,
                ];

                if ($category) {
                    // Mise à jour
                    $category->update($data);
                    $this->categoriesUpdated++;
                } else {
                    // Création
                    Category::create($data);
                    $this->categoriesCreated++;
                }
            });

        } catch (\Exception $e) {
            $categoryName = $categoryData['name'] ?? $categoryData['title'] ?? 'Nom manquant';
            $this->warn("⚠️  Erreur lors du traitement de la catégorie {$categoryName}: " . $e->getMessage());
        }
    }

    private function syncArticlesFromSaas(string $saasUrl, string $apiKey, bool $force, bool $dryRun, ?string $status, int $perPage): void
    {
        // Déterminer la dernière synchronisation
        $lastSync = $this->getLastSyncTime();
        
        if ($lastSync && !$force) {
            $this->info("📅 Dernière synchronisation: {$lastSync->format('d/m/Y H:i:s')}");
            $this->info("🔍 Recherche des articles modifiés depuis cette date...");
        } else {
            $this->info("🆕 Synchronisation complète - Récupération de tous les articles");
        }

        // Préparer les paramètres de requête
        $queryParams = [
            'per_page' => $perPage,
        ];
        
        if ($status) {
            $queryParams['status'] = $status;
        }

        // Ajouter le paramètre "since" si on a une dernière sync
        if ($lastSync && !$force) {
            $queryParams['since'] = $lastSync->toISOString();
        }

        $this->info("📡 Récupération des articles depuis le SaaS...");

        // Faire la requête à l'API du SaaS
        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
                'X-API-Key' => $apiKey,
            ])
            ->get($saasUrl . '/api/articles', $queryParams);

        if (!$response->successful()) {
            $this->error("❌ Erreur API: " . $response->status());
            if ($response->status() === 401) {
                $this->error("🔑 Vérifiez votre clé API");
            } elseif ($response->status() === 404) {
                $this->error("🔍 Endpoint /api/articles non trouvé sur le SaaS");
                $this->warn("💡 Vérifiez que votre SaaS expose bien cet endpoint");
            }
            $this->error("Réponse: " . $response->body());
            throw new \Exception("Erreur lors de la récupération des articles");
        }

        $data = $response->json();
        
        // Gérer différents formats de réponse
        $articles = [];
        if (isset($data['articles'])) {
            $articles = $data['articles'];
        } elseif (isset($data['data'])) {
            $articles = $data['data'];
        } elseif (is_array($data) && isset($data[0])) {
            $articles = $data;
        } else {
            $this->error("❌ Format de réponse non reconnu");
            $this->info("Structure reçue: " . json_encode(array_keys($data), JSON_PRETTY_PRINT));
            throw new \Exception("Format de réponse des articles non reconnu");
        }

        $this->info("📄 Articles récupérés depuis le SaaS: " . count($articles));

        if (empty($articles)) {
            $this->info("✅ Aucun nouvel article à synchroniser");
            return;
        }

        // Traiter les articles
        $this->info("🔄 Traitement des articles...");
        
        $progressBar = $this->output->createProgressBar(count($articles));
        $progressBar->start();

        foreach ($articles as $articleData) {
            if (!$dryRun) {
                $this->processArticle($articleData);
            } else {
                // En mode dry-run, juste simuler
                $externalId = $articleData['id'] ?? $articleData['external_id'] ?? null;
                if ($externalId) {
                    $existingArticle = Article::where('external_id', $externalId)->first();
                    if ($existingArticle) {
                        $this->articlesUpdated++;
                    } else {
                        $this->articlesCreated++;
                    }
                } else {
                    $this->articlesSkipped++;
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    private function processArticle(array $articleData): void
    {
        try {
            DB::transaction(function () use ($articleData) {
                // Déterminer l'external_id
                $externalId = $articleData['id'] ?? $articleData['external_id'] ?? null;
                
                if (empty($externalId)) {
                    $this->articlesSkipped++;
                    return;
                }
                
                // Chercher l'article existant
                $article = Article::where('external_id', $externalId)->first();

                // Préparer les données
                $data = [
                    'title' => $articleData['title'] ?? 'Sans titre',
                    'slug' => $this->generateUniqueSlug($articleData['title'] ?? 'sans-titre', $article?->id),
                    'content' => $articleData['content'] ?? $articleData['excerpt'] ?? 'Contenu non disponible',
                    'excerpt' => $articleData['excerpt'] ?? null,
                    'featured_image_url' => $articleData['featured_image_url'] ?? $articleData['image'] ?? null,
                    'meta_title' => $articleData['meta_title'] ?? $articleData['seo_title'] ?? null,
                    'meta_description' => $articleData['meta_description'] ?? $articleData['seo_description'] ?? null,
                    'status' => $articleData['status'] ?? 'published',
                    'author_name' => $articleData['author_name'] ?? $articleData['author'] ?? 'Auteur SaaS',
                    'author_bio' => $articleData['author_bio'] ?? null,
                    'external_id' => $externalId,
                    'source' => 'saas',
                    'webhook_received_at' => now(),
                    'is_featured' => $articleData['is_featured'] ?? false,
                    'reading_time' => $this->calculateReadingTime($articleData['content'] ?? ''),
                ];

                // Gérer la date de publication
                if (isset($articleData['published_at']) && $articleData['published_at']) {
                    try {
                        $data['published_at'] = Carbon::parse($articleData['published_at']);
                    } catch (\Exception $e) {
                        $data['published_at'] = now();
                    }
                } else {
                    $data['published_at'] = now();
                }

                if ($article) {
                    // Mise à jour
                    $article->update($data);
                    $this->articlesUpdated++;
                } else {
                    // Création
                    $article = Article::create($data);
                    $this->articlesCreated++;
                }

                // Gérer les catégories
                $categories = $articleData['categories'] ?? $articleData['tags'] ?? [];
                if (is_string($categories)) {
                    $categories = explode(',', $categories);
                }
                
                if (is_array($categories) && !empty($categories)) {
                    $this->syncCategories($article, $categories);
                }
            });

        } catch (\Exception $e) {
            $externalId = $articleData['id'] ?? $articleData['external_id'] ?? 'ID manquant';
            $this->warn("⚠️  Erreur lors du traitement de l'article {$externalId}: " . $e->getMessage());
            $this->articlesSkipped++;
        }
    }

    private function syncCategories($article, array $categoryNames): void
    {
        $categoryIds = [];

        foreach ($categoryNames as $categoryName) {
            $categoryName = trim($categoryName);
            if (empty($categoryName)) continue;

            // Chercher ou créer la catégorie
            $category = Category::where('name', $categoryName)->first();
            
            if (!$category) {
                $category = Category::create([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                ]);
                $this->categoriesCreated++;
            }

            $categoryIds[] = $category->id;
        }

        // Synchroniser les relations
        if (!empty($categoryIds)) {
            $article->categories()->sync($categoryIds);
        }
    }

    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        $query = Article::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            $query = Article::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    private function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = ceil($wordCount / 200); // 200 mots par minute
        return max(1, $readingTime);
    }

    private function getLastSyncTime(): ?Carbon
    {
        // Utiliser le dernier article synchronisé depuis le SaaS
        $lastArticle = Article::where('source', 'saas')
            ->whereNotNull('webhook_received_at')
            ->orderBy('webhook_received_at', 'desc')
            ->first();

        return $lastArticle ? $lastArticle->webhook_received_at : null;
    }
} 