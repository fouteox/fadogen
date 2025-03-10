<?php

declare(strict_types=1);

namespace App\Providers;

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

        if ($this->app->environment('production') || $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
