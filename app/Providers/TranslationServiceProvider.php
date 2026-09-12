<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\I18NextTranslationsLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;

final class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(I18NextTranslationsLoader::class, function (Application $app): I18NextTranslationsLoader {
            $files = $app->make(Filesystem::class);

            return new I18NextTranslationsLoader(
                $files,
                new FileLoader($files, $app->langPath()),
                $app->langPath()
            );
        });
    }
}
