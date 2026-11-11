<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\Settings\Models\Settings;

class CountryService
{
    /**
     * Get country by phone code with caching
     */
    public function getCountryByPhoneCode(string $phoneCode): ?object
    {
        $cacheKey = "country:phone_code:{$phoneCode}";

        return Cache::remember($cacheKey, 3600, function () use ($phoneCode) {
            return Settings::countries()
                ->where('data->phone_code', $phoneCode)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get country by ID with caching
     */
    public function getCountryById(int $countryId): ?object
    {
        $cacheKey = "country:id:{$countryId}";

        return Cache::remember($cacheKey, 3600, function () use ($countryId) {
            return Settings::countries()
                ->where('id', $countryId)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Validate phone number length against country rules
     */
    public function validatePhoneLength(string $phone, string $phoneCode): void
    {
        $country = $this->getCountryByPhoneCode($phoneCode);

        if (! $country) {
            throw ValidationException::withMessages([
                'country_code' => ['Invalid country code selected.'],
            ]);
        }

        $countryData = $country->data ?? [];
        $expectedLength = $countryData['phone_length'] ?? 9;
        $actualLength = strlen($phone);

        if ($actualLength !== $expectedLength) {
            throw ValidationException::withMessages([
                'phone' => ["Phone number must be {$expectedLength} digits for {$country->name_en}."],
            ]);
        }
    }

    /**
     * Get full phone number with country code
     */
    public function getFullPhone(string $phone, string $phoneCode): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $cleanCode = preg_replace('/[^0-9]/', '', $phoneCode);

        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = substr($cleanPhone, 1);
        }

        return '+'.$cleanCode.$cleanPhone;
    }

    /**
     * Get country by a full international phone number.
     */
    public function getCountryByFullPhone(string $fullPhone): ?object
    {
        $fullPhone = preg_replace('/[^0-9]/', '', $fullPhone);

        if ($fullPhone === '') {
            return null;
        }

        return Settings::countries()
            ->where('is_active', true)
            ->get()
            ->sortByDesc(fn ($country) => strlen((string) data_get($country->data, 'phone_code', '')))
            ->first(function ($country) use ($fullPhone): bool {
                $phoneCode = (string) data_get($country->data, 'phone_code', '');
                $phoneLength = (int) data_get($country->data, 'phone_length', 9);

                if ($phoneCode === '' || ! str_starts_with($fullPhone, $phoneCode)) {
                    return false;
                }

                $nationalLength = strlen(substr($fullPhone, strlen($phoneCode)));

                return in_array($nationalLength, [$phoneLength, max(0, $phoneLength - 1)], true);
            });
    }

    /**
     * Get country ID from phone code
     */
    public function getCountryIdByPhoneCode(string $phoneCode): ?int
    {
        $country = $this->getCountryByPhoneCode($phoneCode);

        return $country?->id;
    }

    /**
     * Check if country code exists
     */
    public function isValidCountryCode(string $phoneCode): bool
    {
        return $this->getCountryByPhoneCode($phoneCode) !== null;
    }

    /**
     * Get all active countries (for dropdowns)
     */
    public function getAllCountries(): array
    {
        return Cache::remember('countries:all:active', 3600, function () {
            return Settings::countries()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get()
                ->map(function ($country) {
                    $data = $country->data ?? [];

                    return [
                        'id' => $country->id,
                        'name_ar' => $country->name_ar,
                        'name_en' => $country->name_en,
                        'phone_code' => $data['phone_code'] ?? null,
                        'phone_length' => $data['phone_length'] ?? 9,
                        'currency' => $data['currency'] ?? 'SAR',
                        'currency_symbol' => $data['currency_symbol'] ?? '﷼',
                        'code' => $data['code'] ?? null,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Clear country cache (for admin updates)
     */
    public function clearCache(): void
    {
        Cache::forget('countries:all:active');
        // You can add more cache keys here if needed
    }
}
