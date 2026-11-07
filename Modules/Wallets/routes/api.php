<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallets\Http\Controllers\Mobile\ParentWalletController;
use Modules\Wallets\Http\Controllers\Mobile\SubWalletController;

Route::middleware(['security'])->prefix(config('api.version').'/'.config('api.prefixes.mobile'))->group(function (): void {
    Route::middleware(['auth:sanctum', 'abilities:users', 'user.active'])->group(function (): void {
        Route::get('parent-wallet', [ParentWalletController::class, 'show']);
        Route::put('parent-wallet/funding-source', [ParentWalletController::class, 'updateFundingSource']);

        Route::get('sub-wallets', [SubWalletController::class, 'index']);
        Route::post('sub-wallets', [SubWalletController::class, 'store']);
        Route::get('sub-wallets/{subWallet}', [SubWalletController::class, 'show'])->whereNumber('subWallet');
        Route::patch('sub-wallets/{subWallet}', [SubWalletController::class, 'update'])->whereNumber('subWallet');
        Route::delete('sub-wallets/{subWallet}', [SubWalletController::class, 'destroy'])->whereNumber('subWallet');
        Route::post('sub-wallets/{subWallet}/allocate', [SubWalletController::class, 'allocate'])->whereNumber('subWallet');
        Route::post('sub-wallets/{subWallet}/redistribute', [SubWalletController::class, 'redistribute'])->whereNumber('subWallet');
    });
});
