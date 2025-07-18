<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer le canal de notification push personnalisé
        $this->app->make(ChannelManager::class)->extend('push', function ($app) {
            return new \App\Broadcasting\PushChannel($app->make(\App\Services\PushNotificationService::class));
        });
    }
}
