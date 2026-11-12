<?php

declare(strict_types=1);

use App\Http\Controllers\CreateTemplateController;
use Illuminate\Support\Facades\Route;

Route::post('/templates', CreateTemplateController::class)
    ->middleware('throttle:20,1')
    ->name('api.templates.store');
