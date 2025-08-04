<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\DeviceTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health check endpoint for deployment monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'app' => config('app.name'),
        'environment' => config('app.env'),
    ]);
});

// Route pour recevoir les webhooks d'articles depuis votre SaaS
Route::post('/webhook/articles', [WebhookController::class, 'handleArticles'])
    ->name('webhook.articles'); 