<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route pour recevoir les webhooks d'articles depuis votre SaaS
Route::post('/webhook/articles', [WebhookController::class, 'handleArticles'])
    ->name('webhook.articles'); 