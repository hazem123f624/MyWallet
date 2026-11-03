<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\SecurityContext;
use App\Services\Security\Services\SecurityLogService;
use App\Services\Security\Services\ThreatDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #11 — Adaptive Threat Detection                   ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  Traditional security is binary (allow/block). This middleware ║
 * ║  computes a RISK SCORE (0–100) per request, enabling:         ║
 * ║  • Gradual response to threats                                ║
 * ║  • Automatic blocking of high-risk IPs                        ║
 * ║  • Warning-level logging for medium-risk traffic              ║
 * ║  • No impact on legitimate low-risk traffic                   ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  1. Initializes SecurityContext from the request.              ║
 * ║  2. Runs ThreatDetectionService to compute risk score.        ║
 * ║  3. Based on score:                                           ║
 * ║     • High (≥80)  → 403 + auto-block IP + enriched log       ║
 * ║     • Medium (≥50) → log warning, continue                   ║
 * ║     • Low (<50)   → continue silently                         ║
 * ║  4. Attaches SecurityContext to the request for downstream.   ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • No headers or params needed from the client.               ║
 * ║  • Happens automatically on every request.                    ║
 * ║  • If blocked: 403 "Threat detected"                          ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class ThreatDetectionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.threat_detection.enabled', true)) {
            return $next($request);
        }

        // Initialize SecurityContext
        $ctx = SecurityContext::fromRequest($request);

        // Run threat evaluation
        $result = ThreatDetectionService::evaluate(
            $ctx->ip,
            $ctx->userAgent,
            $ctx->requestPath
        );

        $ctx->riskScore = $result['score'];
        $ctx->addFlag('threat_level', $result['level']);

        // Attach context to request for downstream middleware/controllers
        $request->attributes->set('security_context', $ctx);

        // HIGH risk → block immediately
        if ($result['blocked']) {
            SecurityLogService::logEnriched($ctx, 'threat_blocked', [
                'risk_score' => $result['score'],
                'threat_level' => $result['level'],
                'blocked' => true,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Request blocked due to detected threat.',
            ], 403);
        }

        // MEDIUM risk → log warning, continue
        if ($result['level'] === 'medium') {
            SecurityLogService::logEnriched($ctx, 'threat_warning', [
                'risk_score' => $result['score'],
                'threat_level' => $result['level'],
            ]);
        }

        return $next($request);
    }
}
