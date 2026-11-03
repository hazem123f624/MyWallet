<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #2 — Rate Limiting                                ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  Prevents abuse by limiting how many requests a single IP     ║
 * ║  can make in a given time window. Protects against:           ║
 * ║  • Brute-force login attempts                                 ║
 * ║  • API scraping / data harvesting                             ║
 * ║  • DDoS (light-level) or resource exhaustion                  ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  Uses Laravel Cache to count requests per IP per time window. ║
 * ║  Default: 60 requests per minute.                             ║
 * ║  After exceeding the limit, all subsequent requests return    ║
 * ║  HTTP 429 until the window resets.                            ║
 * ║                                                               ║
 * ║  Response headers tell the client their current status:       ║
 * ║  • X-RateLimit-Limit: max requests allowed                   ║
 * ║  • X-RateLimit-Remaining: requests left in window             ║
 * ║  • Retry-After: seconds until the window resets (when 429)    ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • No special headers needed from the client.                 ║
 * ║  • Read X-RateLimit-Remaining to know when to slow down.      ║
 * ║  • On 429, read Retry-After header and wait before retrying.  ║
 * ║  • Implement exponential backoff for best results.            ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.rate_limit.enabled')) {
            return $next($request);
        }

        $ip = $request->ip();
        $maxAttempts = config('security.rate_limit.max_attempts', 60);
        $decayMinutes = config('security.rate_limit.decay_minutes', 1);
        $cacheKey = "security:rate_limit:{$ip}";

        $hits = Cache::get($cacheKey, 0);

        if ($hits >= $maxAttempts) {
            $ttl = Cache::get("{$cacheKey}:ttl", now()->addMinutes($decayMinutes));

            SecurityLogService::log($ip, 'rate_limit', [
                'path' => $request->path(),
                'hits' => $hits,
                'limit' => $maxAttempts,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Too many requests. Please slow down.',
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $decayMinutes * 60,
            ]);
        }

        // Increment counter
        if ($hits === 0) {
            Cache::put($cacheKey, 1, now()->addMinutes($decayMinutes));
            Cache::put("{$cacheKey}:ttl", now()->addMinutes($decayMinutes), now()->addMinutes($decayMinutes));
        } else {
            Cache::increment($cacheKey);
        }

        $response = $next($request);

        // Add rate limit headers to every response
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $hits - 1),
        ]);
    }
}
