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
        ->name('prompts.questions')->where('template', 'laravel')
    );

    Route::get('/generator', [GeneratorController::class, 'index'])->name('generator.index');
    Route::get('/generator/{template}', [GeneratorController::class, 'show'])->name('generator.show');
    Route::post('/generator', [GeneratorController::class, 'store'])->name('generator.store')
        ->middleware([HandlePrecognitiveRequests::class, 'throttle:template-creation']);
});

Route::get('/locales/{locale}/translation.json', FetchTranslationsController::class)->name('i18next.fetch');
