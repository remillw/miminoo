<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\DeviceTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route pour les device tokens (notifications push)
Route::middleware('auth:web')->group(function () {
    Route::post('/device-token', [DeviceTokenController::class, 'store'])->name('api.device-token.store');
});

// Route pour recevoir les webhooks d'articles depuis votre SaaS
Route::post('/webhook/articles', [WebhookController::class, 'handleArticles'])
    ->name('webhook.articles'); 