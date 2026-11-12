<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            App\Http\Middleware\HandleInertiaRequests::class,
            Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ], prepend: [
            SetLocaleMiddleware::class,
        ]);

        // Le conteneur n'est joignable qu'à travers le proxy qui termine le TLS,
        // et la NetworkPolicy n'admet que Traefik. Les pods du cluster sont en
        // 10.42.0.0/16 : sans cela, X-Forwarded-Proto est ignoré et l'URL de
        // téléchargement, signée en https, est rejetée par un 403.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
