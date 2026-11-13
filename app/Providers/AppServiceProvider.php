<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public const string HOME = 'generator.index';

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
        Vite::prefetch(concurrency: 3);

        // Compteur propre aux créations : la limite numérique partagerait la clé
        // domaine et IP du groupe web throttle:60,1.
        RateLimiter::for('template-creation', fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));

        if ($this->app->environment('production') || $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
