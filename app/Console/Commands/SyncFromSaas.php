<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
                           {--categories-only : Synchroniser seulement les catégories}
                           {--sync-images : Télécharger et stocker les images localement}';
    
    protected $description = 'Synchronise les articles et catégories depuis le SaaS vers Laravel';

    private int $articlesCreated = 0;
    private int $articlesUpdated = 0;
    private int $categoriesCreated = 0;
    private int $categoriesUpdated = 0;
    private int $articlesSkipped = 0;
    private int $imagesDownloaded = 0;

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
        $syncImages = $this->option('sync-images');

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
                $this->syncArticlesFromSaas($saasUrl, $apiKey, $force, $dryRun, $status, $perPage, $syncImages);
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
                ['Images téléchargées', $this->imagesDownloaded],
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

    private function syncArticlesFromSaas(string $saasUrl, string $apiKey, bool $force, bool $dryRun, ?string $status, int $perPage, bool $syncImages): void
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
                $this->processArticle($articleData, $syncImages);
            } else {
                // En mode dry-run, juste simuler
                $externalId = $articleData['id'] ?? $articleData['external_id'] ?? null;
                
                // Debug: Afficher les données d'images en mode dry-run
                if ($syncImages) {
                    $this->info("🔍 Analyse des images - Article ID: {$externalId}");
                    
                    // Vérifier les images disponibles
                    $coverImageUrl = $articleData['cover_image'] ?? $articleData['featured_image_url'] ?? $articleData['image'] ?? null;
                    $hasOgImage = !empty($articleData['og_image']);
                    $hasTwitterImage = !empty($articleData['twitter_image']);
                    
                    if ($coverImageUrl) {
                        $this->info("✅ Image de couverture détectée: {$coverImageUrl}");
                        $this->imagesDownloaded++;
                    } else {
                        $this->warn("⚠️  Aucune image de couverture trouvée (cover_image, featured_image_url, image sont null)");
                    }
                    
                    if ($hasOgImage) {
                        $this->info("✅ Image OG détectée: {$articleData['og_image']}");
                        $this->imagesDownloaded++;
                    }
                    
                    if ($hasTwitterImage) {
                        $this->info("✅ Image Twitter détectée: {$articleData['twitter_image']}");
                        $this->imagesDownloaded++;
                    }
                    
                    // Vérifier les images dans le contenu
                    $content = $articleData['content'] ?? '';
                    $contentHtml = $articleData['content_html'] ?? '';
                    $allContent = $content . ' ' . $contentHtml;
                    
                    if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $allContent, $matches)) {
                        $imageCount = count($matches[1]);
                        $this->info("✅ {$imageCount} image(s) détectée(s) dans le contenu");
                        foreach ($matches[1] as $imageUrl) {
                            $this->info("  - {$imageUrl}");
                        }
                        $this->imagesDownloaded += $imageCount;
                    } else {
                        $this->warn("⚠️  Aucune image trouvée dans le contenu");
                        if (strlen($allContent) > 0) {
                            $this->info("📄 Contenu analysé: " . Str::limit($allContent, 100));
                        }
                    }
                    
                    $this->newLine();
                }
                
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

    private function processArticle(array $articleData, bool $syncImages): void
    {
        try {
            DB::transaction(function () use ($articleData, $syncImages) {
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
                    'content' => $articleData['content'] ?? 'Contenu non disponible',
                    'content_html' => $articleData['content_html'] ?? $articleData['content'] ?? null,
                    'excerpt' => $articleData['excerpt'] ?? null,
                    'cover_image' => null, // Sera mis à jour après téléchargement si syncImages est activé
                    'meta_title' => $articleData['meta_title'] ?? $articleData['seo_title'] ?? $articleData['title'] ?? null,
                    'meta_description' => $articleData['meta_description'] ?? $articleData['seo_description'] ?? null,
                    'meta_keywords' => $this->parseKeywords($articleData['meta_keywords'] ?? $articleData['keywords'] ?? null),
                    'canonical_url' => $articleData['canonical_url'] ?? null,
                    'status' => $articleData['status'] ?? 'published',
                    'scheduled_at' => $this->parseDate($articleData['scheduled_at'] ?? null),
                    'is_featured' => $articleData['is_featured'] ?? false,
                    'reading_time' => $this->calculateReadingTime($articleData['content'] ?? $articleData['content_html'] ?? ''),
                    'word_count' => $this->calculateWordCount($articleData['content'] ?? $articleData['content_html'] ?? ''),
                    'author_name' => $articleData['author_name'] ?? $articleData['author'] ?? 'Auteur SaaS',
                    'author_bio' => $articleData['author_bio'] ?? null,
                    'og_title' => $articleData['og_title'] ?? $articleData['title'] ?? null,
                    'og_description' => $articleData['og_description'] ?? $articleData['meta_description'] ?? null,
                    'og_image' => null, // Sera mis à jour après téléchargement si syncImages est activé
                    'twitter_title' => $articleData['twitter_title'] ?? $articleData['title'] ?? null,
                    'twitter_description' => $articleData['twitter_description'] ?? $articleData['meta_description'] ?? null,
                    'twitter_image' => null, // Sera mis à jour après téléchargement si syncImages est activé
                    'schema_markup' => $this->parseSchemaMarkup($articleData['schema_markup'] ?? null),
                    'source' => 'saas',
                    'external_id' => $externalId,
                    'webhook_received_at' => now(),
                    'webhook_data' => $articleData,
                    'is_synced' => true,
                ];

                // Télécharger et stocker les images localement si demandé
                if ($syncImages) {
                    // Télécharger l'image de couverture
                    $coverImageUrl = $articleData['cover_image'] ?? $articleData['featured_image_url'] ?? $articleData['image'] ?? null;
                    if ($coverImageUrl) {
                        $coverImagePath = $this->downloadAndStoreImage($coverImageUrl, $externalId, 'cover');
                        if ($coverImagePath) {
                            $data['cover_image'] = $coverImagePath;
                        }
                    }
                    
                    // Télécharger l'image OG si différente
                    if (isset($articleData['og_image']) && $articleData['og_image'] !== $coverImageUrl) {
                        $ogImagePath = $this->downloadAndStoreImage($articleData['og_image'], $externalId, 'og');
                        if ($ogImagePath) {
                            $data['og_image'] = $ogImagePath;
                        }
                    }
                    
                    // Télécharger l'image Twitter si différente
                    if (isset($articleData['twitter_image']) && $articleData['twitter_image'] !== $coverImageUrl) {
                        $twitterImagePath = $this->downloadAndStoreImage($articleData['twitter_image'], $externalId, 'twitter');
                        if ($twitterImagePath) {
                            $data['twitter_image'] = $twitterImagePath;
                        }
                    }
                    
                    // Traiter les images dans le contenu (téléchargement et remplacement des URLs)
                    $downloadedUrls = []; // Pour éviter les doublons entre content et content_html
                    
                    if (!empty($data['content'])) {
                        $result = $this->downloadContentImages($data['content'], $externalId, $downloadedUrls);
                        $data['content'] = $result['content'];
                        $downloadedUrls = $result['downloadedUrls'];
                    }
                    
                    // Traiter les images dans le contenu HTML si présent ET différent
                    if (!empty($data['content_html']) && $data['content_html'] !== $data['content']) {
                        $result = $this->downloadContentImages($data['content_html'], $externalId, $downloadedUrls);
                        $data['content_html'] = $result['content'];
                    }
                }

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
        $wordCount = $this->calculateWordCount($content);
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

    private function downloadAndStoreImage(string $imageUrl, string $externalId, string $type = 'cover'): ?string
    {
        try {
            $this->info("📥 Téléchargement de l'image {$type}...");

            // Télécharger l'image
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                $this->warn("⚠️  Erreur lors de la récupération de l'image {$type}: " . $response->status());
                return null;
            }

            $imageContent = $response->body();
            
            // Déterminer l'extension du fichier
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Extension par défaut
            }

            // Créer le nom du fichier
            $filename = "articles/{$externalId}_{$type}.{$extension}";
            
            // Stocker l'image localement
            Storage::disk('public')->put($filename, $imageContent);

            $this->info("✅ Image {$type} téléchargée et stockée avec succès !");
            $this->imagesDownloaded++;
            
            // Retourner l'URL locale complète
            if ($type === 'cover') {
                return $filename; // Pour cover_image, on retourne juste le path
            } else {
                return asset('storage/' . $filename); // Pour les images du contenu, on retourne l'URL complète
            }

        } catch (\Exception $e) {
            $this->warn("⚠️  Erreur lors du téléchargement de l'image {$type}: " . $e->getMessage());
            return null;
        }
    }

    private function parseKeywords($keywords): ?array
    {
        if (empty($keywords)) {
            return null;
        }

        if (is_array($keywords)) {
            return $keywords;
        }

        if (is_string($keywords)) {
            return array_map('trim', explode(',', $keywords));
        }

        return null;
    }

    private function parseSchemaMarkup($schemaMarkup): ?array
    {
        if (empty($schemaMarkup)) {
            return null;
        }

        if (is_array($schemaMarkup)) {
            return $schemaMarkup;
        }

        if (is_string($schemaMarkup)) {
            $decoded = json_decode($schemaMarkup, true);
            return $decoded ?: null;
        }

        return null;
    }

    private function parseDate($date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function calculateWordCount(string $content): int
    {
        return str_word_count(strip_tags($content));
    }

    private function downloadContentImages(string $content, string $externalId, array &$downloadedUrls = []): array
    {
        if (empty($content)) {
            return ['content' => $content, 'downloadedUrls' => $downloadedUrls];
        }

        // Pattern pour détecter les images dans le contenu (HTML et Markdown)
        $patterns = [
            // Images HTML: <img src="..." />
            '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            // Images Markdown: ![alt](url)
            '/!\[[^\]]*\]\(([^)]+)\)/i',
        ];

        $imageCounter = count($downloadedUrls) + 1; // Continuer la numérotation

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $imageUrl = $match[1];
                
                // Vérifier si c'est une URL complète et qu'elle n'a pas déjà été téléchargée
                if (filter_var($imageUrl, FILTER_VALIDATE_URL) && !in_array($imageUrl, $downloadedUrls)) {
                    $localImageUrl = $this->downloadAndStoreImage($imageUrl, $externalId, "content_{$imageCounter}");
                    if ($localImageUrl) {
                        $content = str_replace($imageUrl, $localImageUrl, $content);
                        $this->info("🖼️  Image du contenu remplacée: {$imageUrl} -> {$localImageUrl}");
                    }
                    $downloadedUrls[] = $imageUrl;
                    $imageCounter++;
                }
            }
        }

        return ['content' => $content, 'downloadedUrls' => $downloadedUrls];
    }
} 