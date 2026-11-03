<?php

namespace App\Traits;

use App\Services\CountryService;

trait HasPhoneValidation
{
    /**
     * Validate phone number using CountryService
     */
    protected function validatePhoneNumber(string $phone, string $countryCode): void
    {
        app(CountryService::class)->validatePhoneLength($phone, $countryCode);
    }

    /**
     * Get full phone number
     */
    protected function getFullPhoneNumber(string $phone, string $countryCode): string
    {
        return app(CountryService::class)->getFullPhone($phone, $countryCode);
    }

    /**
     * Get country ID from phone code
     */
    protected function getCountryIdFromCode(string $countryCode): ?int
    {
        return app(CountryService::class)->getCountryIdByPhoneCode($countryCode);
    }

    /**
     * Check if country code is valid
     */
    protected function isValidCountryCode(string $countryCode): bool
    {
        return app(CountryService::class)->isValidCountryCode($countryCode);
    }
}