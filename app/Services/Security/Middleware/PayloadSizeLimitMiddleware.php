<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #4 — Payload Size Limit                           ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  Protects the server from excessively large request bodies    ║
 * ║  that can cause:                                              ║
 * ║  • Memory exhaustion (JSON bombs / deeply nested objects)     ║
 * ║  • Slow JSON parsing blocking the event loop                  ║
 * ║  • Disk fill attacks if body is written to temp files         ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  Checks Content-Length header against the configured max.     ║
 * ║  Default limit: 512 KB. Rejects with 413 if exceeded.        ║
 * ║  This runs BEFORE PHP parses the body, so the server          ║
 * ║  never wastes memory on huge payloads.                        ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • Keep request bodies under the configured limit (512 KB).   ║
 * ║  • For file uploads, use a dedicated upload endpoint that     ║
 * ║    skips this middleware.                                     ║
 * ║  • If exceeded, the response is: 413 Payload Too Large        ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class PayloadSizeLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.payload_size.enabled')) {
            return $next($request);
        }

        // File uploads are validated by each endpoint's FormRequest (file + max
        // rules), which returns a clear 422 error instead of a generic 413.
        if ($this->isFileUpload($request)) {
            return $next($request);
        }

        $maxKb = config('security.payload_size.max_kb', 512);
        $maxBytes = $maxKb * 1024;
        $contentLength = $request->header('Content-Length', 0);

        if ((int) $contentLength > $maxBytes) {
            SecurityLogService::log($request->ip(), 'payload_too_large', [
                'path' => $request->path(),
                'content_length' => $contentLength,
                'max_allowed' => $maxBytes,
            ]);

            return response()->json([
                'status' => false,
                'message' => "Payload too large. Maximum allowed: {$maxKb} KB.",
            ], 413);
        }

        return $next($request);
    }

    private function isFileUpload(Request $request): bool
    {
        return str_starts_with((string) $request->header('Content-Type', ''), 'multipart/form-data');
    }
}
