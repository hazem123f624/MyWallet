<?php

use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\Dashboard\DashboardAuthController;
use App\Http\Controllers\Dashboard\DashboardPageController;
use App\Http\Middleware\BlockInProduction;
use App\Http\Middleware\EnsureDashboardAdmin;
use App\Http\Middleware\EnsureDashboardSuperAdmin;
use Illuminate\Support\Facades\Route;

// Development-only pages (welcome + API documentation). BlockInProduction returns
// HTTP 403 for these when APP_ENV=production; every other environment is unaffected.
Route::middleware(BlockInProduction::class)->group(function (): void {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/docs', [ApiDocumentationController::class, 'index'])->name('api-docs-home');
});

Route::prefix('dashboard')->name('dashboard.')->group(function (): void {
    Route::get('login', [DashboardAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [DashboardAuthController::class, 'login'])->name('login.store');
    Route::post('logout', [DashboardAuthController::class, 'logout'])
        ->middleware(EnsureDashboardAdmin::class)
        ->name('logout');

    Route::middleware(EnsureDashboardAdmin::class)->group(function (): void {
        Route::get('/', [DashboardPageController::class, 'overview'])->name('overview');
        Route::get('users', [DashboardPageController::class, 'users'])->name('users');
        Route::get('activity-logs', [DashboardPageController::class, 'activityLogs'])->name('activity-logs');
        Route::get('profile', [DashboardPageController::class, 'profile'])->name('profile');
        Route::get('admins', [DashboardPageController::class, 'admins'])
            ->middleware(EnsureDashboardSuperAdmin::class)
            ->name('admins');
        Route::get('roles', [DashboardPageController::class, 'roles'])
            ->middleware(EnsureDashboardSuperAdmin::class)
            ->name('roles');
        Route::get('models', [DashboardPageController::class, 'models'])
            ->middleware(EnsureDashboardSuperAdmin::class)
            ->name('models');
    });
});
