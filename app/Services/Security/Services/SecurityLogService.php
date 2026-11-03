<?php

namespace App\Services\Security\Services;

use App\Services\Security\Models\SecurityLog;
use App\Services\Security\Services\SecurityContext;
use Illuminate\Support\Facades\Cache;

/**
 * SecurityLogService
 *
 * Centralized service for logging all security events (blocked IPs,
 * failed validations, probing attempts, etc.) to the security_logs table.
 *
 * Also handles 404 probing detection:
 * - Tracks 404 count per IP in cache
 * - If an IP exceeds the configured threshold, auto-blocks it
 */
class SecurityLogService
{
    /**
     * Log a security event to the database.
     *
     * @param  string  $ip  The client IP address
     * @param  string  $eventType  One of: blocked_ip, rate_limit, invalid_key,
     *                             invalid_signature, invalid_timestamp, probing,
     *                             payload_too_large, invalid_content_type, cors_blocked
     * @param  array  $details  Additional context (path, headers, etc.)
     */
    public static function log(string $ip, string $eventType, array $details = []): void
    {
        if (! config('security.logging.enabled', true)) {
            return;
        }

        SecurityLog::create([
            'ip' => $ip,
            'event_type' => $eventType,
            'details' => $details,
        ]);
    }

    /**
     * Log an enriched security event using the SecurityContext.
     *
     * Writes to ALL new columns (risk_score, fingerprint, nonce, flags, etc.)
     * in addition to the standard fields. Use this from the new middleware.
     *
     * @param  SecurityContext  $ctx        The request security context
     * @param  string          $eventType  Event type identifier
     * @param  array           $details    Additional context (merged into details JSON)
     */
    public static function logEnriched(SecurityContext $ctx, string $eventType, array $details = []): void
    {
        if (! config('security.logging.enabled', true)) {
            return;
        }

        $attributes = $ctx->toLogAttributes();
        $attributes['event_type'] = $eventType;
        $attributes['details'] = $details;

        SecurityLog::create($attributes);
    }

    /**
     * Track a 404 hit for the given IP.
     * If the IP exceeds the probing threshold, auto-block it via cache.
     *
     * @param  string  $ip  The client IP
     * @return bool True if the IP was just blocked due to probing
     */
    public static function track404(string $ip): bool
    {
        if (! config('security.probing_detection.enabled', true)) {
            return false;
        }

        $cacheKey = "security:404_count:{$ip}";
        $max = config('security.probing_detection.max_404_per_minute', 10);
        $blockMin = config('security.probing_detection.block_duration_min', 30);

        $count = Cache::increment($cacheKey);

        // Set TTL on first hit (1 minute window)
        if ($count === 1) {
            Cache::put($cacheKey, 1, now()->addMinute());
        }

        if ($count >= $max) {
            // Auto-block this IP
            Cache::put("security:blocked_ip:{$ip}", true, now()->addMinutes($blockMin));
            Cache::forget($cacheKey);

            self::log($ip, 'probing', [
                'reason' => "Exceeded {$max} 404s in 1 minute",
                'blocked_for_minutes' => $blockMin,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check if an IP is dynamically blocked (e.g. from probing detection).
     */
    public static function isBlocked(string $ip): bool
    {
        return Cache::has("security:blocked_ip:{$ip}");
    }
}
