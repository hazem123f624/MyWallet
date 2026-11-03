<?php

namespace App\Services\Security\Controllers;

use App\Services\Security\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  API Key Renewal Controller                                   ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  PURPOSE:                                                     ║
 * ║  Allows clients to renew their API keys BEFORE they expire.   ║
 * ║  The client sends the current valid key and gets a new one.   ║
 * ║                                                               ║
 * ║  FLOW:                                                        ║
 * ║  ─────                                                        ║
 * ║  1. Client checks expires_at from /api/v1/security/key-info  ║
 * ║  2. If expiring soon (e.g. < 7 days), sends:                 ║
 * ║     POST /api/v1/security/renew-key                           ║
 * ║     Header: X-API-Token: current_key                          ║
 * ║  3. Server validates the current key is still valid           ║
 * ║  4. Server rotates keys and returns new credentials           ║
 * ║  5. Client saves new credentials and uses them going forward  ║
 * ║                                                               ║
 * ║  SECURITY NOTES:                                              ║
 * ║  • Only active, non-expired keys can renew                    ║
 * ║  • Old key becomes invalid immediately upon rotation          ║
 * ║  • Renewal is rate-limited by the security middleware group   ║
 * ║  • Signature validation required (prevents stolen key abuse)  ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class ApiKeyController extends Controller
{
    /**
     * Get current key info (expiration, status).
     *
     * GET /api/v1/security/key-info
     *
     * The client uses this to decide when to trigger renewal.
     */
    public function keyInfo(Request $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');

        if (! $client) {
            return response()->json([
                'status' => false,
                'message' => 'API client not resolved.',
            ], 401);
        }

        $daysRemaining = null;
        $shouldRenew = false;

        if ($client->expires_at) {
            $daysRemaining = (int) now()->diffInDays($client->expires_at, false);
            $renewThreshold = config('security.api_key.renew_threshold_days', 7);
            $shouldRenew = $daysRemaining <= $renewThreshold && $daysRemaining >= 0;
        }

        return response()->json([
            'status' => true,
            'data' => [
                'client_name' => $client->name,
                'expires_at' => $client->expires_at?->toIso8601String(),
                'days_remaining' => $daysRemaining,
                'should_renew' => $shouldRenew,
                'is_active' => $client->is_active,
                'last_rotated_at' => $client->last_rotated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Renew (rotate) the client's API key and secret.
     *
     * POST /api/v1/security/renew-key
     *
     * Returns the new credentials. The old key becomes invalid
     * immediately — the client MUST save and use the new ones.
     */
    public function renewKey(Request $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');

        if (! $client) {
            return response()->json([
                'status' => false,
                'message' => 'API client not resolved.',
            ], 401);
        }

        // Rotate keys with default expiry
        $client = ApiKeyService::rotateKeys($client);

        return response()->json([
            'status' => true,
            'message' => 'API key renewed successfully. Save the new credentials immediately.',
            'data' => [
                'api_key' => $client->api_key,
                'secret' => $client->secret,
                'expires_at' => $client->expires_at?->toIso8601String(),
                'days_remaining' => $client->expires_at
                    ? (int) now()->diffInDays($client->expires_at, false)
                    : null,
            ],
        ]);
    }
}
