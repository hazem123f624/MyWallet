<?php

namespace App\Services\Security\Services;

use Illuminate\Support\Facades\Cache;

/**
 * NonceService — Redis-based one-time-use token validation.
 *
 * ┌──────────────────────────────────────────────────────────────┐
 * │  FLOW                                                        │
 * │                                                              │
 * │  1. Client calls GET /api/v1/security/nonce                  │
 * │     → Server generates nonce, stores in Redis, returns it    │
 * │                                                              │
 * │  2. Client includes nonce in next request:                   │
 * │     Header: X-NONCE: <nonce_value>                           │
 * │                                                              │
 * │  3. Server validates:                                        │
 * │     a. Nonce exists in Redis → valid                         │
 * │     b. Delete from Redis (one-time use)                      │
 * │     c. Nonce NOT in Redis → reject (expired or replayed)     │
 * │                                                              │
 * │  WHY? Prevents replay attacks at the request level.          │
 * │  Even if someone captures a signed request with valid        │
 * │  timestamp, they cannot replay it because the nonce is       │
 * │  consumed on first use.                                      │
 * └──────────────────────────────────────────────────────────────┘
 *
 * Storage: Redis only (no DB table needed).
 * Key format: security:nonce:{nonce}
 */
class NonceService
{
    /**
     * Generate a new nonce and store it in Redis.
     *
     * @return string The generated nonce value
     */
    public static function generate(): string
    {
        $nonce = bin2hex(random_bytes(32));
        $ttl = config('security.nonce.ttl_seconds', 300);

        Cache::put("security:nonce:{$nonce}", true, now()->addSeconds($ttl));

        return $nonce;
    }

    /**
     * Validate and consume a nonce (one-time use).
     *
     * @param  string  $nonce  The nonce to validate
     * @return bool True if valid (existed and was consumed), false if invalid/expired/replayed
     */
    public static function validate(string $nonce): bool
    {
        if (empty($nonce)) {
            return false;
        }

        $cacheKey = "security:nonce:{$nonce}";

        if (! Cache::has($cacheKey)) {
            return false;
        }

        // Consume the nonce — delete immediately (one-time use)
        Cache::forget($cacheKey);

        return true;
    }

    /**
     * Check if a nonce exists without consuming it.
     * Useful for debugging / inspection only.
     */
    public static function exists(string $nonce): bool
    {
        return Cache::has("security:nonce:{$nonce}");
    }
}
