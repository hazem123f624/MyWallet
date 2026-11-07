<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Dashboard\DashboardAnalyticsController;
use Illuminate\Support\Facades\Route;
use Modules\Admins\Http\Controllers\AdminsController;
use Modules\Admins\Http\Controllers\ModelsController;
use Modules\Admins\Http\Controllers\RolesController;

Route::middleware(['security'])->prefix(config('api.version'))->group(function (): void {
    Route::middleware(['auth:sanctum', 'abilities:admins'])->prefix(config('api.prefixes.dashboard'))->group(function (): void {

        Route::get('analytics/overview', [DashboardAnalyticsController::class, 'overview']);

        // ── Admins ──────────────────────────────────────────
        Route::get('admins/trash', [AdminsController::class, 'trash']);
        Route::patch('admins/{admin}/restore', [AdminsController::class, 'restore'])->withTrashed();
        Route::delete('admins/{admin}/force-delete', [AdminsController::class, 'remove'])->withTrashed();
        Route::patch('admins/{admin}/toggle-active', [AdminsController::class, 'switchActive']);
        Route::delete('admins/{admin}/roles/{role}', [AdminsController::class, 'removeRole']);
        Route::get('/profile', [AdminsController::class, 'profile']);
        Route::put('/profile', [AdminsController::class, 'updateProfile']);
        Route::post('/change-password', [AdminsController::class, 'changePassword']);
        Route::get('admins', [AdminsController::class, 'index']);
        Route::post('admins', [AdminsController::class, 'store']);
        Route::get('admins/{admin}', [AdminsController::class, 'show']);
        Route::put('admins/{admin}', [AdminsController::class, 'update']);
        Route::delete('admins/{admin}', [AdminsController::class, 'destroy']);
        Route::get('counts', [AdminsController::class, 'getDashboardCounts']);

        // ── Roles ───────────────────────────────────────────
        Route::get('roles/trash', [RolesController::class, 'trash']);
        Route::patch('roles/{role}/restore', [RolesController::class, 'restore'])->withTrashed();
        Route::delete('roles/{role}/force-delete', [RolesController::class, 'remove'])->withTrashed();
        Route::patch('roles/{role}/toggle-active', [RolesController::class, 'switchActive']);

        Route::get('roles', [RolesController::class, 'index']);
        Route::post('roles', [RolesController::class, 'store']);
        Route::get('roles/{role}/admins', [RolesController::class, 'admins']);
        Route::post('roles/{role}/admins', [RolesController::class, 'assignAdmins']);
        Route::get('roles/{role}/available-admins', [RolesController::class, 'availableAdmins']);
        Route::get('roles/{role}/permissions', [RolesController::class, 'permissions']);
        Route::get('roles/{role}', [RolesController::class, 'show']);
        Route::put('roles/{role}', [RolesController::class, 'update']);
        Route::delete('roles/{role}', [RolesController::class, 'destroy']);

        // ── Modules (Models) ────────────────────────────────
        Route::get('models', [ModelsController::class, 'index']);

        // ── Activity Logs ──────────────────────────────────
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('activity-logs/export/{fileName}/download', [ActivityLogController::class, 'downloadExport'])->where('fileName', '[A-Za-z0-9_.-]+')->name('activity_logs.export.download');
        Route::get('activity-logs/{subject_id}/export', [ActivityLogController::class, 'exportSubject'])->whereNumber('subject_id');
        Route::get('activity-logs/{subject_id}', [ActivityLogController::class, 'subject'])->whereNumber('subject_id');
    });

    Route::middleware(['auth:sanctum', 'abilities:admins'])->group(function (): void {
        Route::post('logout', [AdminsController::class, 'logout'])->name('logout');
    });

    Route::post('login', [AdminsController::class, 'login'])->name('login');
    Route::post('verify-forget-password-otp', [AdminsController::class, 'verifyForgotPassword'])->name('verify_forget_password_otp');
    Route::post('resend-otp', [AdminsController::class, 'resendOtp'])->name('resend_otp');
    Route::post('resend-forgot-password-otp', [AdminsController::class, 'resendForgotPasswordOtp'])->name('resend_forgot_password_otp');
    Route::post('forget-password', [AdminsController::class, 'forgetPassword'])->name('forget_password');
    Route::post('reset-password', [AdminsController::class, 'resetPassword'])->name('reset_password');
});
