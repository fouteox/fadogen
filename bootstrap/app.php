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

        $middleware->trustProxies(at: [
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
