<?php

namespace App\Services\Security\Services;

/**
 * SignatureService
 *
 * Generates and verifies HMAC signatures for request integrity.
 *
 * ┌─────────────────────────────────────────────────────────────┐
 * │  SIGNATURE FORMULA                                         │
 * │                                                            │
 * │  signature = HMAC-SHA256(                                  │
 * │      payload  = METHOD|FULL_URL|TIMESTAMP|BODY,            │
 * │      key      = client_secret                              │
 * │  )                                                         │
 * │                                                            │
 * │  Example:                                                  │
 * │  payload  = "POST|https://api.example.com/v1/admins|       │
 * │              1708250400|{"name":"John"}"                    │
 * │  key      = "abc123..."                                    │
 * │  result   = "e3b0c44298fc1c149afbf4c..."                   │
 * └─────────────────────────────────────────────────────────────┘
 *
 * HOW TO INTEGRATE (for mobile / web / third-party):
 * ─────────────────────────────────────────────────
 * 1. You receive an api_key and a secret from the dashboard admin.
 * 2. Before sending any API request, build the payload string:
 *      payload = HTTP_METHOD + "|" + FULL_URL + "|" + UNIX_TIMESTAMP + "|" + REQUEST_BODY
 *    For GET requests with no body, the body part is empty string.
 * 3. Generate the HMAC-SHA256 hash of the payload using your secret.
 * 4. Send the hash in the X-SIGNATURE header.
 *
 * JavaScript example:
 *   const crypto = require('crypto');
 *   const payload = `POST|https://api.example.com/v1/admins|${timestamp}|${JSON.stringify(body)}`;
 *   const signature = crypto.createHmac('sha256', secret).update(payload).digest('hex');
 *
 * Swift example:
 *   import CryptoKit
 *   let payload = "\(method)|\(url)|\(timestamp)|\(body)"
 *   let key = SymmetricKey(data: Data(secret.utf8))
 *   let signature = HMAC<SHA256>.authenticationCode(for: Data(payload.utf8), using: key)
 *       .map { String(format: "%02x", $0) }.joined()
 */
class SignatureService
{
    /**
     * Generate a signature for the given request components.
     *
     * @param  string  $method  HTTP method (GET, POST, etc.)
     * @param  string  $url  Full request URL
     * @param  string  $timestamp  Unix timestamp
     * @param  string  $body  Raw request body (empty string for GET)
     * @param  string  $secret  The client's secret key
     * @return string Hex-encoded HMAC-SHA256 signature
     */
    public static function generate(
        string $method,
        string $url,
        string $timestamp,
        string $body,
        string $secret
    ): string {
        $algorithm = config('security.signature.algorithm', 'sha256');
        $payload = strtoupper($method).'|'.$url.'|'.$timestamp.'|'.$body;
        return hash_hmac($algorithm, $payload, $secret);
    }

    /**
     * Verify a request signature against the expected one.
     *
     * Uses hash_equals for timing-safe comparison to prevent
     * timing attacks that could leak signature bytes.
     *
     * @param  string  $expectedSignature  The HMAC we computed server-side
     * @param  string  $providedSignature  The HMAC the client sent in X-SIGNATURE
     */
    public static function verify(string $expectedSignature, string $providedSignature): bool
    {
        return hash_equals($expectedSignature, $providedSignature);
    }
}
