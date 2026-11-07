<?php

use Illuminate\Support\Facades\Route;
use Modules\BankAccounts\Http\Controllers\Mobile\BankAccountController;
use Modules\BankAccounts\Http\Controllers\Mobile\BankController;

Route::middleware(['security'])->prefix(config('api.version').'/'.config('api.prefixes.mobile'))->group(function (): void {
    Route::middleware(['auth:sanctum', 'abilities:users', 'user.active'])->group(function (): void {
        Route::get('banks', [BankController::class, 'index']);

        Route::get('bank-accounts', [BankAccountController::class, 'index']);
        Route::post('bank-accounts', [BankAccountController::class, 'store']);
        Route::post('bank-accounts/{bankAccount}/resend-otp', [BankAccountController::class, 'resendOtp'])->whereNumber('bankAccount');
        Route::post('bank-accounts/{bankAccount}/verify', [BankAccountController::class, 'verify'])->whereNumber('bankAccount');
        Route::patch('bank-accounts/{bankAccount}/primary', [BankAccountController::class, 'setPrimary'])->whereNumber('bankAccount');
        Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->whereNumber('bankAccount');
    });
});
