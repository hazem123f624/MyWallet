<?php

use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\Dashboard\DashboardAuthController;
use App\Http\Controllers\Dashboard\DashboardPageController;
use App\Http\Controllers\User\UserAuthController;
use App\Http\Controllers\User\UserPageController;
use App\Http\Middleware\BlockInProduction;
use App\Http\Middleware\EnsureDashboardAdmin;
use App\Http\Middleware\EnsureDashboardSuperAdmin;
use App\Http\Middleware\EnsureUserDashboard;
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
        Route::get('transfers', [DashboardPageController::class, 'transfers'])->name('transfers');
        Route::get('beneficiaries', [DashboardPageController::class, 'beneficiaries'])->name('beneficiaries');
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

Route::prefix('user')->name('user.')->group(function (): void {
    Route::get('login', [UserAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [UserAuthController::class, 'login'])->name('login.store');
    Route::post('logout', [UserAuthController::class, 'logout'])
        ->middleware(EnsureUserDashboard::class)
        ->name('logout');

    Route::middleware(EnsureUserDashboard::class)->group(function (): void {
        Route::get('/', [UserPageController::class, 'overview'])->name('overview');
        Route::get('wallets', [UserPageController::class, 'wallets'])->name('wallets');
        Route::get('bank-accounts', [UserPageController::class, 'bankAccounts'])->name('bank-accounts');
        Route::get('profile', [UserPageController::class, 'profile'])->name('profile');
        Route::get('transfers', [UserPageController::class, 'transfers'])->name('transfers');
        Route::get('transactions', [UserPageController::class, 'transactions'])->name('transactions');
        Route::get('beneficiaries', [UserPageController::class, 'beneficiaries'])->name('beneficiaries');
        Route::get('security', [UserPageController::class, 'security'])->name('security');
    });
});
