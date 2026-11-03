<?php

namespace App\Services\Security\Middleware;

use App\Services\Security\Services\SecurityLogService;
use App\Services\Security\Services\SignatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ╔═══════════════════════════════════════════════════════════════╗
 * ║  MIDDLEWARE #9 — HMAC Signature Validation                    ║
 * ╠═══════════════════════════════════════════════════════════════╣
 * ║                                                               ║
 * ║  WHY?                                                         ║
 * ║  ─────                                                        ║
 * ║  This is the STRONGEST layer. It ensures:                     ║
 * ║  1. REQUEST INTEGRITY — the body was not tampered with        ║
 * ║  2. AUTHENTICATION — only someone with the secret can sign    ║
 * ║  3. NON-REPUDIATION — the request came from the real client   ║
 * ║                                                               ║
 * ║  Even if an attacker steals the API key, they CANNOT forge    ║
 * ║  requests without also knowing the secret (which is never     ║
 * ║  sent over the network).                                      ║
 * ║                                                               ║
 * ║  HOW IT WORKS                                                 ║
 * ║  ─────────────                                                ║
 * ║  1. Client builds the payload string:                         ║
 * ║       METHOD|FULL_URL|TIMESTAMP|BODY                          ║
 * ║  2. Client signs it with HMAC-SHA256 using their secret.      ║
 * ║  3. Client sends the signature in X-SIGNATURE header.         ║
 * ║  4. Server rebuilds the same payload from the incoming        ║
 * ║     request, signs it with the client's stored secret,        ║
 * ║     and compares signatures using timing-safe comparison.     ║
 * ║                                                               ║
 * ║  ┌────────────────────────────────────────────────────────┐   ║
 * ║  │  PAYLOAD FORMAT:                                       │   ║
 * ║  │  POST|https://api.example.com/api/v1/admins|           │   ║
 * ║  │  1708250400|{"name":"John","email":"j@test.com"}       │   ║
 * ║  └────────────────────────────────────────────────────────┘   ║
 * ║                                                               ║
 * ║  INTEGRATION NOTES (Mobile / Web / Third-party)               ║
 * ║  ──────────────────────────────────────────────                ║
 * ║  • You need TWO things: api_key (public) + secret (private)   ║
 * ║  • Before every request, compute the HMAC:                    ║
 * ║                                                               ║
 * ║    JavaScript:                                                ║
 * ║    ──────────                                                 ║
 * ║    const crypto = require('crypto');                           ║
 * ║    const ts = Math.floor(Date.now() / 1000).toString();       ║
 * ║    const body = JSON.stringify(data);                         ║
 * ║    const payload = `POST|${fullUrl}|${ts}|${body}`;           ║
 * ║    const sig = crypto.createHmac('sha256', secret)            ║
 * ║                      .update(payload).digest('hex');          ║
 * ║    headers['X-SIGNATURE'] = sig;                              ║
 * ║    headers['X-TIMESTAMP'] = ts;                               ║
 * ║    headers['X-API-Token'] = apiKey;                           ║
 * ║                                                               ║
 * ║    Swift:                                                     ║
 * ║    ──────                                                     ║
 * ║    import CryptoKit                                           ║
 * ║    let ts = String(Int(Date().timeIntervalSince1970))         ║
 * ║    let payload = "\(method)|\(url)|\(ts)|\(body)"             ║
 * ║    let key = SymmetricKey(data: Data(secret.utf8))            ║
 * ║    let sig = HMAC<SHA256>.authenticationCode(                  ║
 * ║        for: Data(payload.utf8), using: key                    ║
 * ║    ).map { String(format: "%02x", $0) }.joined()              ║
 * ║                                                               ║
 * ║    Kotlin:                                                    ║
 * ║    ───────                                                    ║
 * ║    import javax.crypto.Mac                                    ║
 * ║    import javax.crypto.spec.SecretKeySpec                     ║
 * ║    val ts = (System.currentTimeMillis()/1000).toString()      ║
 * ║    val payload = "$method|$url|$ts|$body"                     ║
 * ║    val mac = Mac.getInstance("HmacSHA256")                    ║
 * ║    mac.init(SecretKeySpec(secret.toByteArray(),"HmacSHA256")) ║
 * ║    val sig = mac.doFinal(payload.toByteArray())               ║
 * ║              .joinToString("") { "%02x".format(it) }         ║
 * ║                                                               ║
 * ║  • For GET requests with no body, use empty string: ""        ║
 * ║  • URL must be the FULL URL including query string            ║
 * ║  • If invalid: 401 "Invalid signature"                        ║
 * ║                                                               ║
 * ╚═══════════════════════════════════════════════════════════════╝
 */
class SignatureMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enabled') || ! config('security.signature.enabled')) {
            return $next($request);
        }

        $headerName = config('security.signature.header_name', 'X-SIGNATURE');
        $signature = $request->header($headerName);

        if (! $signature) {
            return response()->json([
                'status' => false,
                'message' => "Missing required header: {$headerName}",
            ], 401);
        }

        // Get the client (attached by ApiKeyMiddleware)
        $client = $request->attributes->get('api_client');

        if (! $client) {
            return response()->json([
                'status' => false,
                'message' => 'API client not resolved. Ensure API key middleware runs first.',
            ], 401);
        }

        // Build expected signature
        $timestamp = $request->header(config('security.timestamp.header_name', 'X-TIMESTAMP'), '');
        $rawBody = $request->getContent();
        $body = (empty($rawBody) || $rawBody === '[]') ? '' : $rawBody;

        $expected = SignatureService::generate(
            method: $request->method(),
            url: $request->fullUrl(),
            timestamp: $timestamp,
            body: $body,
            secret: $client->secret
        );
        // Timing-safe comparison
        if (! SignatureService::verify($expected, $signature)) {
            SecurityLogService::log($request->ip(), 'invalid_signature', [
                'path' => $request->path(),
                'client' => $client->name,
                'sent_signature' => substr($signature, 0, 16).'...',
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid request signature.',
            ], 401);
        }

        return $next($request);
    }
}
