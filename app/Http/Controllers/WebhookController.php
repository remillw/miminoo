<?php

namespace App\Http\Controllers;

use App\Services\WebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookHandler $webhookHandler
    ) {}

    public function handleArticles(Request $request)
    {
        try {
            // Vérifier la clé API
            $apiKey = $request->header('X-API-Key');
            $expectedApiKey = config('webhook.api_key');
            
            if (!$apiKey || $apiKey !== $expectedApiKey) {
                Log::warning('Invalid API key attempt', [
                    'provided_key' => $apiKey ? substr($apiKey, 0, 8) . '...' : 'none',
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid API key'
                ], 401);
            }

            // Validation de base
            $validated = $request->validate([
                'action' => 'required|in:upsert,delete,sync',
                
                // Pour action upsert/sync - single article
                'external_id' => 'required_without:articles|string',
                'title' => 'required_with:external_id|string|max:255',
                'content' => 'required_with:external_id|string',
                'excerpt' => 'nullable|string',
                'status' => 'nullable|in:draft,published,scheduled',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'featured_image_url' => 'nullable|url',
                'author_name' => 'nullable|string|max:255',
                'author_bio' => 'nullable|string',
                'published_at' => 'nullable|date',
                'categories' => 'nullable|array',
                'categories.*' => 'string',
                
                // Pour action upsert/sync - multiple articles
                'articles' => 'required_without:external_id|array',
                'articles.*.external_id' => 'required|string',
                'articles.*.title' => 'required|string|max:255',
                'articles.*.content' => 'required|string',
                'articles.*.excerpt' => 'nullable|string',
                'articles.*.status' => 'nullable|in:draft,published,scheduled',
                'articles.*.meta_title' => 'nullable|string|max:255',
                'articles.*.meta_description' => 'nullable|string',
                'articles.*.featured_image_url' => 'nullable|url',
                'articles.*.author_name' => 'nullable|string|max:255',
                'articles.*.author_bio' => 'nullable|string',
                'articles.*.published_at' => 'nullable|date',
                'articles.*.categories' => 'nullable|array',
                'articles.*.categories.*' => 'string',
                
                // Pour action delete
                'external_ids' => 'required_if:action,delete|array',
                'external_ids.*' => 'string',
            ]);

            // Traiter le webhook
            $results = $this->webhookHandler->handleWebhook($validated);

            Log::info('Webhook processed successfully', [
                'action' => $validated['action'],
                'results_count' => count($results)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'results' => $results
            ]);

        } catch (ValidationException $e) {
            Log::warning('Webhook validation failed', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 