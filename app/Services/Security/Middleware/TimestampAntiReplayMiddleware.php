<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #8 — Timestamp & Anti-Replay                      ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  Prevents REPLAY ATTACKS. Without this, an attacker who       ║
 * ║  intercepts a valid request could re-send it hours or days    ║
 * ║  later and it would still be accepted. Examples:              ║
 * ║  • Replaying a "transfer money" request                      ║
 * ║  • Replaying a "create admin" request                        ║
 * ║  • Replaying a "reset password" request                      ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  1. Client sends current Unix timestamp in X-TIMESTAMP.       ║
 * ║  2. Server compares it to its own clock.                      ║
 * ║  3. If the difference exceeds tolerance (default ± 5 min),    ║
 * ║     the request is rejected as expired/replayed.              ║
 * ║  4. Combined with HMAC signature (next layer), the timestamp  ║
 * ║     cannot be forged because it's part of the signed payload. ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • Add this header to EVERY request:                          ║
 * ║      X-TIMESTAMP: <current_unix_timestamp>                    ║
 * ║  • Example values:                                            ║
 * ║      JavaScript:  Math.floor(Date.now() / 1000)               ║
 * ║      Swift:       Int(Date().timeIntervalSince1970)           ║
 * ║      Kotlin:      System.currentTimeMillis() / 1000           ║
 * ║      PHP:         time()                                      ║
 * ║  • IMPORTANT: Ensure your device/server clock is accurate.    ║
 * ║    Use NTP sync if running on a server.                       ║
 * ║  • If expired: 401 "Request expired"                          ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class TimestampAntiReplayMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.timestamp.enabled')) {
            return $next($request);
        }

        $headerName = config('security.timestamp.header_name', 'X-TIMESTAMP');
        $toleranceSec = config('security.timestamp.tolerance_sec', 300);
        $timestamp = $request->header($headerName);

        if (! $timestamp) {
            return response()->json([
                'status' => false,
                'message' => "Missing required header: {$headerName}",
            ], 401);
        }

        // Must be a valid numeric timestamp
        if (! is_numeric($timestamp)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid timestamp format. Must be Unix timestamp.',
            ], 401);
        }

        $diff = abs(time() - (int) $timestamp);

        if ($diff > $toleranceSec) {
            SecurityLogService::log($request->ip(), 'invalid_timestamp', [
                'path' => $request->path(),
                'sent_timestamp' => $timestamp,
                'server_time' => time(),
                'diff_seconds' => $diff,
                'tolerance' => $toleranceSec,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Request expired. Timestamp is outside the allowed window.',
            ], 401);
        }

        return $next($request);
    }
}
