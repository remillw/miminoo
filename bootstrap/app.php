<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\CheckBabysitterVerification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['sidebar_state']);

        // Exclure les webhooks Stripe et les routes de broadcasting de la vérification CSRF
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'broadcasting/auth',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'check.babysitter.verification' => \App\Http\Middleware\CheckBabysitterVerification::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configuration des pages d'erreur personnalisées pour Inertia
        $exceptions->respond(function ($response, $exception, $request) {
            $statusCode = $response->getStatusCode();
            
            // Skip API requests
            if ($request->expectsJson()) {
                return $response;
            }
            
            // Handle specific error codes with Inertia pages
            switch ($statusCode) {
                case 500:
                case 503:
                    return Inertia::render('Errors/500')->toResponse($request)->setStatusCode($statusCode);
                case 403:
                    return Inertia::render('Errors/403')->toResponse($request)->setStatusCode(403);
                case 404:
                    return Inertia::render('Errors/404')->toResponse($request)->setStatusCode(404);
                default:
                    return $response;
            }
        });
    })->create();
