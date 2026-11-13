<?php

declare(strict_types=1);

use App\Http\Controllers\DependenciesDetectionController;
use App\Http\Controllers\DownloadTemplateController;
use App\Http\Controllers\FetchTranslationsController;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\InitController;
use App\Http\Controllers\QuestionsController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

Route::get('/deploy', function () {
    return Inertia\Inertia::render('deploy');
})->name('deploy');

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/init/{template?}', [InitController::class, 'index'])
        ->name('init')
        ->where('template', 'laravel');

    Route::get('/template/{template}', [InitController::class, 'withTemplate'])
        ->name('init.template');

    Route::get('/templates/{template}/download', DownloadTemplateController::class)
        ->middleware(['signed'])
        ->name('templates.download');

    Route::get('/dependencies/detect', [DependenciesDetectionController::class, 'detect'])
        ->name('dependencies.detect');

    Route::localizedGroup(fn () => Route::get('/prompts/{template}', QuestionsController::class)
        ->where('template', 'laravel')
        ->name('prompts.questions')
    );

    Route::get('/generator', [GeneratorController::class, 'index'])->name('generator.index');
    Route::get('/generator/{template}', [GeneratorController::class, 'show'])->name('generator.show');
    Route::post('/generator', [GeneratorController::class, 'store'])->name('generator.store')
        ->middleware([HandlePrecognitiveRequests::class]);
});

// Le catalogue ne dépend que de la locale de l'URL : il sort du groupe web
// (ni session ni cookie) pour pouvoir être mis en cache.
Route::get('/locales/{locale}/translation.json', FetchTranslationsController::class)
    ->withoutMiddleware('web')
    ->middleware('cache.headers:public;max_age=3600;etag')
    ->name('i18next.fetch');
