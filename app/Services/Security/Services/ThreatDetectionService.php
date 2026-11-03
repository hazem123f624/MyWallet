<?php

namespace App\Services\Security\Services;

use App\Services\Security\Models\SecurityLog;
use Illuminate\Support\Facades\Cache;

/**
 * ThreatDetectionService — Adaptive risk scoring engine.
 *
 * Computes a 0–100 risk_score per request by evaluating multiple signals:
 *
 * ┌──────────────────────────────────────────────────────────────┐
 * │  RISK SIGNALS                                                │
 * │                                                              │
 * │  Signal                          │ Max Points               │
 * │  ────────────────────────────────┼──────────────────────     │
 * │  Recent security events (1h)     │ +30 (scaled)             │
 * │  Known-bad User-Agent patterns   │ +40                      │
 * │  Rate-limit proximity            │ +15 (scaled)             │
 * │  Missing common headers          │ +10                      │
 * │  Suspicious path patterns        │ +5                       │
 * │                                                              │
 * │  THRESHOLDS (configurable):                                  │
 * │  Low    < 25  → no action                                   │
 * │  Medium < 50  → log warning                                 │
 * │  High   ≥ 80  → auto-block IP                               │
 * └──────────────────────────────────────────────────────────────┘
 *
 * Reads from:  security_logs table (existing), rate-limit cache keys (existing)
 * Writes to:   Cache blocked-ip keys (existing mechanism)
 */
class ThreatDetectionService
{
    /**
     * Calculate the risk score for a given IP + user-agent.
     *
     * @param  string       $ip         Client IP address
     * @param  string|null  $userAgent  Raw User-Agent header
     * @param  string|null  $path       Request path
     * @return int  Risk score 0–100
     */
    public static function calculateRiskScore(
        string $ip,
        ?string $userAgent = null,
        ?string $path = null
    ): int {
        $score = 0;

        $score += self::scoreRecentEvents($ip);
        $score += self::scoreBadUserAgent($userAgent);
        $score += self::scoreRateLimitProximity($ip);
        $score += self::scoreMissingHeaders($userAgent);
        $score += self::scoreSuspiciousPath($path);

        return min(100, max(0, $score));
    }

    /**
     * Evaluate risk and optionally block the IP if threshold is exceeded.
     *
     * @return array{score: int, level: string, blocked: bool}
     */
    public static function evaluate(
        string $ip,
        ?string $userAgent = null,
        ?string $path = null
    ): array {
        $score = self::calculateRiskScore($ip, $userAgent, $path);
        $highThreshold = config('security.threat_detection.high_threshold', 80);
        $mediumThreshold = config('security.threat_detection.medium_threshold', 50);
        $blockMinutes = config('security.threat_detection.auto_block_minutes', 60);

        $blocked = false;
        $level = 'low';

        if ($score >= $highThreshold) {
            $level = 'high';
            // Auto-block using the existing cache mechanism
            Cache::put("security:blocked_ip:{$ip}", true, now()->addMinutes($blockMinutes));
            $blocked = true;
        } elseif ($score >= $mediumThreshold) {
            $level = 'medium';
        }

        return [
            'score' => $score,
            'level' => $level,
            'blocked' => $blocked,
        ];
    }

    /**
     * Score based on recent security events from the existing security_logs table.
     * More recent events for this IP = higher risk.
     *
     * 0 events → 0 points
     * 1–3 events → 5 points
     * 4–9 events → 15 points
     * 10+ events → 30 points
     */
    private static function scoreRecentEvents(string $ip): int
    {
        $cacheKey = "security:threat:events_count:{$ip}";

        // Cache the count for 2 minutes to avoid excessive queries
        $count = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($ip) {
            return SecurityLog::where('ip', $ip)
                ->where('created_at', '>=', now()->subHour())
                ->count();
        });

        return match (true) {
            $count >= 10 => 30,
            $count >= 4 => 15,
            $count >= 1 => 5,
            default => 0,
        };
    }

    /**
     * Score based on known-bad user-agent patterns.
     * These patterns indicate scanners, bots, or attack tools.
     */
    private static function scoreBadUserAgent(?string $userAgent): int
    {
        if (empty($userAgent)) {
            return 5; // Missing UA is mildly suspicious
        }

        $patterns = config('security.threat_detection.bad_ua_patterns', [
            'sqlmap', 'nikto', 'nmap', 'masscan', 'zgrab',
            'dirbuster', 'gobuster', 'wfuzz', 'hydra', 'burp',
        ]);

        $ua = strtolower($userAgent);

        foreach ($patterns as $pattern) {
            if (str_contains($ua, strtolower($pattern))) {
                return 40;
            }
        }

        return 0;
    }

    /**
     * Score based on how close the IP is to the rate limit.
     * Uses the existing rate-limit cache keys.
     */
    private static function scoreRateLimitProximity(string $ip): int
    {
        $cacheKey = "security:rate_limit:{$ip}";
        $hits = Cache::get($cacheKey, 0);
        $maxAttempts = config('security.rate_limit.max_attempts', 60);

        if ($maxAttempts <= 0) {
            return 0;
        }

        $ratio = $hits / $maxAttempts;

        return match (true) {
            $ratio >= 0.9 => 15,
            $ratio >= 0.7 => 10,
            $ratio >= 0.5 => 5,
            default => 0,
        };
    }

    /**
     * Score for missing standard headers (indicates non-browser/bot traffic).
     */
    private static function scoreMissingHeaders(?string $userAgent): int
    {
        if (empty($userAgent)) {
            return 10;
        }

        // Very short UA strings are suspicious
        if (strlen($userAgent) < 10) {
            return 5;
        }

        return 0;
    }

    /**
     * Score for suspicious path patterns (common scanner targets).
     */
    private static function scoreSuspiciousPath(?string $path): int
    {
        if (empty($path)) {
            return 0;
        }

        $suspiciousPatterns = [
            '.env', 'wp-admin', 'wp-login', 'phpmyadmin', 'phpinfo',
            '.git', '.svn', 'admin/config', 'setup.php', 'install.php',
            'xmlrpc.php', 'wp-content', 'wp-includes',
        ];

        $lowerPath = strtolower($path);

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($lowerPath, $pattern)) {
                return 5;
            }
        }

        return 0;
    }
}
