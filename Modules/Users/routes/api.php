<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\ManageUsersController;
use Modules\Users\Http\Controllers\Mobile\UsersController;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['security'])->prefix('v1/mobile/users')->group(function (): void {
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
    Route::middleware(['auth:sanctum', 'abilities:users'])->group(function (): void {
        Route::get('profile', [UsersController::class, 'profile']);
        Route::post('profile', [UsersController::class, 'updateProfile']);
        Route::delete('profile', [UsersController::class, 'destroy']);
        Route::post('change-password', [UsersController::class, 'changePassword']);
        Route::post('logout', [UsersController::class, 'logout']);
        Route::post('refresh-token', [UsersController::class, 'refreshToken']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['security', 'auth:sanctum', 'abilities:admins'])->prefix('v1/dashboard/users')->group(function (): void {
    Route::get('trash', [ManageUsersController::class, 'trash']);
    Route::patch('{user}/restore', [ManageUsersController::class, 'restore'])->withTrashed();
    Route::delete('{user}/force-delete', [ManageUsersController::class, 'remove'])->withTrashed();
    Route::patch('{user}/toggle-active', [ManageUsersController::class, 'switchActive']);

    Route::get('/', [ManageUsersController::class, 'index']);
    Route::get('{user}', [ManageUsersController::class, 'show']);
    Route::delete('{user}', [ManageUsersController::class, 'destroy']);
});
