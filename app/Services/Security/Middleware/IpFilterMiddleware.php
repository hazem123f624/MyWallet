<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #1 — IP Filter (Whitelist / Blacklist)           ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  The very first line of defense. Before doing ANY processing, ║
 * ║  check if the requesting IP is allowed to reach your API.     ║
 * ║  This stops known attackers, scanners, and unauthorized       ║
 * ║  networks before they consume any server resources.           ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  Two modes:                                                   ║
 * ║  1. WHITELIST mode: If whitelist is NOT empty, ONLY those     ║
 * ║     IPs can access the API. Everything else is blocked.       ║
 * ║  2. BLACKLIST mode: If whitelist IS empty, all IPs are        ║
 * ║     allowed EXCEPT those in the blacklist.                    ║
 * ║                                                               ║
 * ║  Also checks dynamically-blocked IPs (from probing detection).║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • No headers or params needed from the client.               ║
 * ║  • If your team uses a VPN, add the VPN exit IP to whitelist. ║
 * ║  • Cloud services (AWS, GCP) should whitelist their NAT IPs.  ║
 * ║  • If blocked, the response is: 403 Forbidden                 ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class IpFilterMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.ip_filter.enabled')) {
            return $next($request);
        }

        $ip = $request->ip();
        $whitelist = array_filter(config('security.ip_filter.whitelist', []));
        $blacklist = array_filter(config('security.ip_filter.blacklist', []));

        // Check dynamic blocks (probing detection)
        if (SecurityLogService::isBlocked($ip)) {
            SecurityLogService::log($ip, 'blocked_ip', [
                'reason' => 'Dynamically blocked (probing)',
                'path' => $request->path(),
            ]);
            abort(403, 'Forbidden');
        }

        // Whitelist mode: only whitelisted IPs pass
        if (! empty($whitelist) && ! in_array($ip, $whitelist)) {
            SecurityLogService::log($ip, 'blocked_ip', [
                'reason' => 'Not in whitelist',
                'path' => $request->path(),
            ]);
            abort(403, 'Forbidden');
        }

        // Blacklist mode: block known-bad IPs
        if (in_array($ip, $blacklist)) {
            SecurityLogService::log($ip, 'blocked_ip', [
                'reason' => 'Blacklisted',
                'path' => $request->path(),
            ]);
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
