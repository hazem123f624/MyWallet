<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\ManageUsersController;
use Modules\Users\Http\Controllers\Mobile\TransactionPinController;
use Modules\Users\Http\Controllers\Mobile\UsersController;
use Modules\Users\Http\Middleware\EnsureUserIsActive;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['security'])->prefix(config('api.version').'/'.config('api.prefixes.mobile').'/users')->group(function (): void {
    // Auth Flow
    Route::post('register', [UsersController::class, 'register']);
    Route::post('login', [UsersController::class, 'login']);
    Route::post('verify', [UsersController::class, 'verifyAccount']);
    Route::post('resend-otp', [UsersController::class, 'resendOtp']);

    // Password Reset Flow
    Route::post('forget-password', [UsersController::class, 'forgetPassword']);
    Route::post('verify-forget-password', [UsersController::class, 'verifyForgotPassword']);
    Route::post('reset-password', [UsersController::class, 'resetPassword']);

    // Protected Routes
    Route::middleware(['auth:sanctum', 'abilities:users', EnsureUserIsActive::class])->group(function (): void {
        Route::get('profile', [UsersController::class, 'profile']);
        Route::post('profile', [UsersController::class, 'updateProfile']);
        Route::delete('profile', [UsersController::class, 'destroy']);
        Route::post('change-password', [UsersController::class, 'changePassword']);
        Route::post('transaction-pin', [TransactionPinController::class, 'set']);
        Route::post('transaction-pin/change', [TransactionPinController::class, 'change']);
        Route::post('transaction-pin/forget', [TransactionPinController::class, 'sendResetOtp']);
        Route::post('transaction-pin/reset', [TransactionPinController::class, 'reset']);
        Route::post('transaction-pin/verify', [TransactionPinController::class, 'verify']);
        Route::post('logout', [UsersController::class, 'logout']);
        Route::post('refresh-token', [UsersController::class, 'refreshToken']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['security', 'auth:sanctum', 'abilities:admins'])->prefix(config('api.version').'/'.config('api.prefixes.dashboard').'/users')->group(function (): void {
    Route::get('trash', [ManageUsersController::class, 'trash']);
    Route::get('statistics', [ManageUsersController::class, 'statistics']);
    Route::patch('{user}/restore', [ManageUsersController::class, 'restore'])->withTrashed();
    Route::delete('{user}/force-delete', [ManageUsersController::class, 'remove'])->withTrashed();
    Route::patch('{user}/toggle-active', [ManageUsersController::class, 'switchActive']);

    Route::get('/', [ManageUsersController::class, 'index']);
    Route::get('{user}', [ManageUsersController::class, 'show']);
    Route::delete('{user}', [ManageUsersController::class, 'destroy']);
});
