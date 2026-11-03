<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\SettingsController;

Route::middleware(['auth:sanctum'])->prefix(config('api.version'))->group(function (): void {
    Route::get('settings/{type}', [SettingsController::class, 'index']);
    Route::post('settings/{type}', [SettingsController::class, 'store']);
    Route::get('settings/{type}/{id}', [SettingsController::class, 'show']);
    Route::put('settings/{type}/{id}', [SettingsController::class, 'update']);
    Route::patch('settings/{type}/{id}/toggle-active', [SettingsController::class, 'switchActive']);
    Route::delete('settings/{type}/{data}', [SettingsController::class, 'destroy']);

    Route::get('locations/country', [SettingsController::class, 'countries']);
    Route::get('locations/country/search', [SettingsController::class, 'searchCountries']);
    Route::get('locations/city', [SettingsController::class, 'cities']);
    Route::get('locations/city/search', [SettingsController::class, 'searchCities']);
    Route::get('locations/country/{country}/cities', [SettingsController::class, 'citiesByCountry']);
    Route::get('locations/nationality', [SettingsController::class, 'nationalities']);

});
