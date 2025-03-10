<?php

declare(strict_types=1);

use App\Http\Controllers\CreateTemplateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/templates', CreateTemplateController::class)->name('api.templates.store');
