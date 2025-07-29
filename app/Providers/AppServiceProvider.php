<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Auth\Middleware\Authenticate;

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
        // 🔹 Redéfinir la redirection si l'utilisateur n'est pas authentifié
        Authenticate::redirectUsing(function ($request) {
            return route('connexion'); // ou '/connexion'
        });

        // 🔹 Enregistrer le canal de notification push personnalisé
        $this->app->resolving(ChannelManager::class, function (ChannelManager $manager) {
            $manager->extend('push', function ($app) {
                return new \App\Broadcasting\PushChannel(
                    $app->make(\App\Services\PushNotificationService::class)
                );
            });
        });
    }
}
