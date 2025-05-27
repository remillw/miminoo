<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookHandler
{
    public function handleWebhook(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $action = $data['action'] ?? 'upsert';
            $results = [];

            switch ($action) {
                case 'upsert':
                    $results = $this->upsertArticles($data);
                    break;
                
                case 'delete':
                    $results = $this->deleteArticles($data);
                    break;
                
                case 'sync':
                    $results = $this->syncAllArticles($data);
                    break;
                
                default:
                    throw new \Exception("Unknown webhook action: {$action}");
            }

            return $results;
        });
    }

    private function upsertArticles(array $data): array
    {
        $results = [];
        $articles = $data['articles'] ?? [$data]; // Support single article ou array

        foreach ($articles as $articleData) {
            try {
                $article = $this->upsertSingleArticle($articleData);
                $results[] = [
                    'success' => true,
                    'external_id' => $articleData['external_id'],
                    'article_id' => $article->id,
                    'action' => 'upserted'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'external_id' => $articleData['external_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                Log::error('Failed to upsert article', [
                    'external_id' => $articleData['external_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    private function upsertSingleArticle(array $data): Article
    {
        $externalId = $data['external_id'];
        
        // Chercher l'article existant
        $article = Article::where('external_id', $externalId)->first();

        $articleData = [
            'title' => $data['title'],
            'content' => $data['content'], // HTML reçu
            'excerpt' => $data['excerpt'] ?? null,
            'featured_image_url' => $data['featured_image_url'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'author_name' => $data['author_name'] ?? null,
            'author_bio' => $data['author_bio'] ?? null,
            'external_id' => $externalId,
            'source' => 'webhook',
            'webhook_received_at' => now(),
            'webhook_data' => $data,
        ];

        // Gérer la date de publication
        if (isset($data['published_at'])) {
            $articleData['published_at'] = $data['published_at'];
        } elseif ($data['status'] === 'published' && !$article) {
            $articleData['published_at'] = now();
        }

        if ($article) {
            // Mise à jour
            $article->update($articleData);
            Log::info('Article updated via webhook', [
                'external_id' => $externalId,
                'article_id' => $article->id
            ]);
        } else {
            // Création
            $article = Article::create($articleData);
            Log::info('Article created via webhook', [
                'external_id' => $externalId,
                'article_id' => $article->id
            ]);
        }

        // Gérer les catégories
        if (isset($data['categories']) && is_array($data['categories'])) {
            $this->syncCategories($article, $data['categories']);
        }

        return $article;
    }

    private function deleteArticles(array $data): array
    {
        $results = [];
        $externalIds = $data['external_ids'] ?? [$data['external_id']]; // Support single ou array

        foreach ($externalIds as $externalId) {
            try {
                $article = Article::where('external_id', $externalId)->firstOrFail();
                
                $article->delete();
                
                $results[] = [
                    'success' => true,
                    'external_id' => $externalId,
                    'action' => 'deleted'
                ];
                
                Log::info('Article deleted via webhook', [
                    'external_id' => $externalId
                ]);
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'external_id' => $externalId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    private function syncAllArticles(array $data): array
    {
        // Synchronisation complète : supprime les articles non présents dans le webhook
        $externalIds = collect($data['articles'])->pluck('external_id')->toArray();
        
        // Supprimer les articles qui ne sont plus dans la liste
        $deletedCount = Article::whereNotIn('external_id', $externalIds)->delete();

        // Upsert tous les articles
        $upsertResults = $this->upsertArticles($data);

        return [
            'deleted_count' => $deletedCount,
            'upsert_results' => $upsertResults
        ];
    }

    private function syncCategories(Article $article, array $categoryNames): void
    {
        $categoryIds = [];

        foreach ($categoryNames as $categoryName) {
            if (empty($categoryName)) continue;

            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );

            $categoryIds[] = $category->id;
        }

        $article->categories()->sync($categoryIds);
    }
} 