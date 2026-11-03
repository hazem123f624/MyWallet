<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\CriticalRequestDetector;
use App\Services\Security\Services\NonceService;
use App\Services\Security\Services\SecurityContext;
use App\Services\Security\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #10 — Nonce Validation                            ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  Prevents replay attacks at the request level. Even with      ║
 * ║  valid timestamp + signature, a captured request CANNOT be    ║
 * ║  replayed because each nonce is consumed on first use.        ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  1. Client requests a nonce: GET /api/v1/security/nonce       ║
 * ║  2. Client sends the nonce in X-NONCE header with the real    ║
 * ║     request.                                                  ║
 * ║  3. Server validates and consumes the nonce (one-time use).   ║
 * ║  4. If nonce is missing, expired, or already used → 401.      ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • Before each mutating request (POST/PUT/PATCH/DELETE),      ║
 * ║    fetch a fresh nonce from the API.                          ║
 * ║  • Include it in the X-NONCE header.                          ║
 * ║  • Each nonce can only be used ONCE.                          ║
 * ║  • Nonces expire after the configured TTL (default 5 min).   ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class NonceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.nonce.enabled')) {
            return $next($request);
        }

        $headerName = config('security.nonce.header_name', 'X-NONCE');
        $nonce = $request->header($headerName);

        // Selective Check: Determine if this request actually needs a nonce
        $isCritical = CriticalRequestDetector::isCritical($request);

        if (! $isCritical) {
            // If it's not critical, but they provided a nonce anyway,
            // we can still attach it to the context, but we don't validate it.
            if ($nonce) {
                $ctx = $request->attributes->get('security_context');
                if ($ctx instanceof SecurityContext) {
                    $ctx->nonce = $nonce;
                }
            }
            return $next($request);
        }

        if (! $nonce) {
            return response()->json([
                'status' => false,
                'message' => "Missing required header: {$headerName} for this critical operation.",
            ], 401);
        }

        if (! NonceService::validate($nonce)) {
            SecurityLogService::log($request->ip(), 'invalid_nonce', [
                'path' => $request->path(),
                'nonce' => substr($nonce, 0, 16) . '...',
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid, expired, or already-used nonce.',
            ], 401);
        }

        // Attach nonce to the security context if it exists
        $ctx = $request->attributes->get('security_context');
        if ($ctx instanceof SecurityContext) {
            $ctx->nonce = $nonce;
        }

        return $next($request);
    }
}
