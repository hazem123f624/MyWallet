<?php

use Illuminate\Support\Facades\Route;
use Modules\Transfers\Http\Controllers\Dashboard\TransferController as DashboardTransferController;
use Modules\Transfers\Http\Controllers\Mobile\TransferController;
use Modules\Transfers\Http\Controllers\Provider\ProviderCallbackController;

Route::middleware(['security'])->prefix(config('api.version').'/'.config('api.prefixes.mobile'))->group(function (): void {
    Route::middleware(['auth:sanctum', 'abilities:users', 'user.active'])->group(function (): void {
        Route::get('transfers', [TransferController::class, 'index']);
        Route::post('transfers', [TransferController::class, 'store']);
        Route::get('transfers/history', [TransferController::class, 'history']);
        Route::get('transfers/limits', [TransferController::class, 'limits']);
        Route::post('transfers/{transfer}/refresh', [TransferController::class, 'refresh'])->whereNumber('transfer')->middleware('throttle:6,1');
        Route::get('transfers/{transfer}', [TransferController::class, 'show'])->whereNumber('transfer');
    });
});

Route::middleware(['security', 'auth:sanctum', 'abilities:admins'])->prefix(config('api.version').'/'.config('api.prefixes.dashboard'))->group(function (): void {
    Route::get('transfers', [DashboardTransferController::class, 'index']);
    Route::get('transfers/statistics', [DashboardTransferController::class, 'statistics']);
    Route::post('transfers/{transfer}/sync', [DashboardTransferController::class, 'sync'])->whereNumber('transfer');
    Route::get('transfers/{transfer}', [DashboardTransferController::class, 'show'])->whereNumber('transfer');
});

Route::middleware(['throttle:60,1'])->prefix(config('api.version'))->group(function (): void {
    Route::post('provider/transfers/callback', ProviderCallbackController::class);
});
