<?php

use App\Http\Controllers\ApiDocumentationController;
use App\Http\Middleware\BlockInProduction;
use Illuminate\Support\Facades\Route;

// Development-only pages (welcome + API documentation). BlockInProduction returns
// HTTP 403 for these when APP_ENV=production; every other environment is unaffected.
Route::middleware(BlockInProduction::class)->group(function (): void {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/docs', [ApiDocumentationController::class, 'index'])->name('api-docs-home');
});
