<?php

use Illuminate\Support\Facades\Route;
use Modules\Beneficiaries\Http\Controllers\Dashboard\BeneficiaryController as DashboardBeneficiaryController;
use Modules\Beneficiaries\Http\Controllers\Mobile\BeneficiaryController;

Route::middleware(['security'])->prefix(config('api.version').'/'.config('api.prefixes.mobile'))->group(function (): void {
    Route::middleware(['auth:sanctum', 'abilities:users', 'user.active'])->group(function (): void {
        Route::get('beneficiaries', [BeneficiaryController::class, 'index']);
        Route::post('beneficiaries', [BeneficiaryController::class, 'store']);
        Route::get('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'show'])->whereNumber('beneficiary');
        Route::patch('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'update'])->whereNumber('beneficiary');
        Route::delete('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'destroy'])->whereNumber('beneficiary');
    });
});

Route::middleware(['security', 'auth:sanctum', 'abilities:admins'])->prefix(config('api.version').'/'.config('api.prefixes.dashboard'))->group(function (): void {
    Route::get('beneficiaries', [DashboardBeneficiaryController::class, 'index']);
    Route::get('beneficiaries/{beneficiary}', [DashboardBeneficiaryController::class, 'show'])->whereNumber('beneficiary');
});
