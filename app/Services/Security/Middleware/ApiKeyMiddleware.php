<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\ApiKeyService;
use App\Services\Security\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #7 — API Key Validation (Enhanced)                ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  ENHANCEMENTS (v2):                                           ║
 * ║  ────────────────                                             ║
 * ║  1. KEY EXPIRATION — Keys now have an optional expires_at.    ║
 * ║     Expired keys are rejected with a clear message.           ║
 * ║  2. ENCRYPTED HEADER — When encryption is enabled, the       ║
 * ║     X-API-Token header value is AES-256-CBC encrypted:        ║
 * ║       encrypt("api_key|timestamp", secret)                    ║
 * ║     This means intercepting the header is useless because:    ║
 * ║     - The value changes every second (timestamp embedded)     ║
 * ║     - You need the secret to decrypt it                       ║
 * ║  3. KEY ROTATION — admins can rotate keys at any time.        ║
 * ║     Old keys immediately become invalid.                      ║
 * ║                                                               ║
 * ║  HOW TO SEND (when encryption is ON):                         ║
 * ║  ─────────────────────────────────────                        ║
 * ║  JavaScript:                                                  ║
 * ║    const ts = Math.floor(Date.now() / 1000).toString();       ║
 * ║    const data = `${apiKey}|${ts}`;                            ║
 * ║    const key = CryptoJS.SHA256(secret).toString().substr(0,32)║
 * ║    const iv  = CryptoJS.lib.WordArray.random(16);             ║
 * ║    const enc = CryptoJS.AES.encrypt(data, key, {iv});         ║
 * ║    const header = btoa(iv.concat(enc.ciphertext).toString()); ║
 * ║    headers['X-API-Token'] = header;                           ║
 * ║                                                               ║
 * ║  When encryption is OFF (default):                            ║
 * ║    headers['X-API-Token'] = apiKey;  // same as before        ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.api_key.enabled')) {
            return $next($request);
        }

        $headerName = config('security.api_key.header_name', 'X-API-Token');
        $headerValue = $request->header($headerName);

        if (! $headerValue) {
            return response()->json([
                'status' => false,
                'message' => "Missing required header: {$headerName}",
            ], 401);
        }

        $client = ApiKeyService::validate($headerValue);

        if (! $client) {
            SecurityLogService::log($request->ip(), 'invalid_key', [
                'path' => $request->path(),
                'api_key' => substr($headerValue, 0, 8).'...',
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid, inactive, or expired API key.',
            ], 401);
        }

        // Check expiration explicitly to give a clear message
        if ($client->isExpired()) {
            SecurityLogService::log($request->ip(), 'expired_key', [
                'path' => $request->path(),
                'client' => $client->name,
                'expired_at' => $client->expires_at->toIso8601String(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'API key has expired. Please request a new key from the administrator.',
                'expired_at' => $client->expires_at->toIso8601String(),
            ], 401);
        }

        // Attach client to request so SignatureMiddleware can access the secret
        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
