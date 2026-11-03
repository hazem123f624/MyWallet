<?php

namespace App\Services\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #3 — Force JSON                                   ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  This is a REST API — all communication must be JSON.         ║
 * ║  Rejecting non-JSON requests early prevents:                  ║
 * ║  • CSRF attacks via HTML form submissions (form-urlencoded)   ║
 * ║  • Accidental XML/multipart payloads reaching controllers     ║
 * ║  • Content-type sniffing vulnerabilities                      ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  1. Forces Accept: application/json on ALL requests, so       ║
 * ║     Laravel always returns JSON errors instead of HTML.       ║
 * ║  2. For POST/PUT/PATCH (requests with body), requires         ║
 * ║     Content-Type: application/json.                           ║
 * ║  3. GET/DELETE/HEAD/OPTIONS are exempt from Content-Type      ║
 * ║     check (they typically don't have a body).                 ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • ALWAYS set these headers on every request:                 ║
 * ║      Accept: application/json                                 ║
 * ║      Content-Type: application/json  (for POST/PUT/PATCH)     ║
 * ║  • Send body as JSON.stringify(), NOT FormData or URLEncoded  ║
 * ║  • If missing, the response is: 406 Not Acceptable            ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class ForceJsonMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.force_json.enabled')) {
            return $next($request);
        }

        // Force Accept header so Laravel returns JSON errors
        $request->headers->set('Accept', 'application/json');

        // For methods that carry a body, require JSON content-type
        $methodsWithBody = ['POST', 'PUT', 'PATCH'];

        if (in_array($request->method(), $methodsWithBody)) {
            $contentType = $request->header('Content-Type', '');
            if (!str_contains($contentType, 'application/json')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Content-Type must be application/json.',
                ], 406);
            }
        }

        return $next($request);
    }
}
