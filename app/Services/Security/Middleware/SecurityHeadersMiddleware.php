<?php

namespace App\Services\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #5 — Security Headers                             ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  Security headers instruct browsers how to handle responses.  ║
 * ║  Without them, your API is vulnerable to:                     ║
 * ║  • Clickjacking (embedding your site in an iframe)            ║
 * ║  • MIME-type sniffing (browser guessing content type)         ║
 * ║  • XSS attacks (if any endpoint returns HTML)                 ║
 * ║  • Downgrade attacks (HTTP instead of HTTPS)                  ║
 * ║  • Information leakage (server version, tech stack)           ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  Adds the following headers to EVERY response:                ║
 * ║                                                               ║
 * ║  Header                          │ What it prevents           ║
 * ║  ────────────────────────────────┼──────────────────────────── ║
 * ║  X-Content-Type-Options: nosniff │ MIME-type sniffing         ║
 * ║  X-Frame-Options: DENY           │ Clickjacking               ║
 * ║  X-XSS-Protection: 1; mode=block │ Reflected XSS (legacy)    ║
 * ║  Strict-Transport-Security       │ HTTP downgrade attacks     ║
 * ║  Referrer-Policy: no-referrer    │ Referrer leakage           ║
 * ║  Permissions-Policy              │ Browser API abuse          ║
 * ║  X-Powered-By: removed           │ Technology fingerprinting  ║
 * ║  Server: removed                 │ Server info leakage        ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • No action required from clients.                           ║
 * ║  • These headers are automatically added to all responses.    ║
 * ║  • Web frontends benefit most (browser enforces these rules). ║
 * ║  • Mobile apps can safely ignore these headers.               ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.enabled') || ! config('security.security_headers.enabled')) {
            return $response;
        }

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Legacy XSS protection (some older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Force HTTPS for 1 year (only effective on HTTPS sites)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Don't send referrer to external sites
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable unnecessary browser APIs
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Remove technology fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
