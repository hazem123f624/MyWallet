<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #6 — CORS Protection                              ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  CORS (Cross-Origin Resource Sharing) prevents malicious      ║
 * ║  websites from making requests to your API using a victim's   ║
 * ║  browser. Without CORS protection, any website could:         ║
 * ║  • Steal data from authenticated sessions                     ║
 * ║  • Perform actions on behalf of logged-in users               ║
 * ║  • Exploit the implicit trust between browser and API          ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  1. For OPTIONS (preflight) requests: immediately respond     ║
 * ║     with CORS headers, never reaching the controller.         ║
 * ║  2. For actual requests: validate the Origin header against   ║
 * ║     the allowed_origins config. If allowed, add CORS headers  ║
 * ║     to the response.                                          ║
 * ║  3. If Origin is not allowed and isn't '*', block with 403.   ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • Mobile apps: CORS does NOT affect you. Browsers enforce    ║
 * ║    CORS, not HTTP clients like mobile SDK.                    ║
 * ║  • Web frontends: Your domain must be in allowed_origins.     ║
 * ║    Ask the API admin to add your origin.                      ║
 * ║  • During development: '*' allows all origins (unsafe for     ║
 * ║    production).                                               ║
 * ║  • Third-party integrations via server-to-server (not        ║
 * ║    browser): CORS does not apply.                             ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.cors.enabled')) {
            return $next($request);
        }

        $allowedOrigins = config('security.cors.allowed_origins', ['*']);
        $allowedMethods = implode(', ', config('security.cors.allowed_methods', []));
        $allowedHeaders = implode(', ', config('security.cors.allowed_headers', []));
        $exposeHeaders = implode(', ', config('security.cors.expose_headers', []));
        $maxAge = config('security.cors.max_age', 86400);
        $origin = $request->header('Origin', '');

        // Handle preflight (OPTIONS) requests immediately
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders([
                'Access-Control-Allow-Origin' => $this->resolveOrigin($origin, $allowedOrigins),
                'Access-Control-Allow-Methods' => $allowedMethods,
                'Access-Control-Allow-Headers' => $allowedHeaders,
                'Access-Control-Max-Age' => $maxAge,
            ]);
        }

        // Validate origin for actual requests
        if ($origin && ! $this->isOriginAllowed($origin, $allowedOrigins)) {
            SecurityLogService::log($request->ip(), 'cors_blocked', [
                'origin' => $origin,
                'path' => $request->path(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Origin not allowed.',
            ], 403);
        }

        $response = $next($request);

        // Add CORS headers to actual response
        $response->headers->set('Access-Control-Allow-Origin', $this->resolveOrigin($origin, $allowedOrigins));
        $response->headers->set('Access-Control-Allow-Methods', $allowedMethods);
        $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);
        $response->headers->set('Access-Control-Expose-Headers', $exposeHeaders);

        return $response;
    }

    /**
     * Determine the Access-Control-Allow-Origin value.
     */
    private function resolveOrigin(string $origin, array $allowedOrigins): string
    {
        if (in_array('*', $allowedOrigins)) {
            return '*';
        }

        return in_array($origin, $allowedOrigins) ? $origin : '';
    }

    /**
     * Check if the origin is in the allowed list.
     */
    private function isOriginAllowed(string $origin, array $allowedOrigins): bool
    {
        return in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins);
    }
}
