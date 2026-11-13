<?php

declare(strict_types=1);

use App\Http\Controllers\CreateTemplateController;
use Illuminate\Support\Facades\Route;

Route::post('/templates', CreateTemplateController::class)
    ->middleware('throttle:template-creation')
    ->name('api.templates.store');
