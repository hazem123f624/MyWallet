<?php

namespace App\Services\Security\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CriticalRequestDetector — Logic to identify "critical" requests.
 *
 * Decisions are based on:
 * 1. HTTP Methods (e.g., POST, PUT, DELETE)
 * 2. Path patterns (e.g., api/v1/payments/*)
 * 3. Exclusion lists
 *
 * This centralizes the logic for "Should this request be nonce-protected?"
 */
class CriticalRequestDetector
{
    /**
     * Determine if the given request is considered critical and requires a nonce.
     */
    public static function isCritical(Request $request): bool
    {
        $path = $request->path();
        $method = strtoupper($request->method());

        // 1. Check Exclusions (higher priority)
        foreach (config('security.nonce.exclude_routes', []) as $pattern) {
            if ($request->is($pattern) || Str::is($pattern, $path)) {
                return false;
            }
        }

        // 2. Check Explicit Critical Routes
        foreach (config('security.nonce.critical_routes', []) as $pattern) {
            if ($request->is($pattern) || Str::is($pattern, $path)) {
                return true;
            }
        }

        // 3. Default: Logical Criticality (Write operations)
        $criticalMethods = config('security.nonce.only_for', ['POST', 'PUT', 'PATCH', 'DELETE']);
        if (in_array($method, $criticalMethods)) {
            return true;
        }

        return false;
    }
}
