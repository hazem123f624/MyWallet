<?php

namespace App\Services\Security\Services;

use App\Services\Security\Models\ApiClient;
use Illuminate\Support\Facades\Cache;

/**
 * ApiKeyService — Enhanced with Expiration, Encrypted Headers & Key Rotation.
 *
 * ┌────────────────────────────────────────────────────────────────┐
 * │  FLOW: How Encrypted API-KEY Works                            │
 * │                                                               │
 * │  Client Side:                                                 │
 * │  ─────────────                                                │
 * │  1. Take api_key + current timestamp                          │
 * │  2. Build: "api_key|timestamp"                                │
 * │  3. Encrypt with AES-256-CBC using secret as key              │
 * │  4. Base64 encode the result                                  │
 * │  5. Send in X-API-Token header                                │
 * │                                                               │
 * │  Server Side:                                                 │
 * │  ─────────────                                                │
 * │  1. Check if header looks encrypted (base64 format)           │
 * │  2. Try each active client's secret to decrypt                │
 * │  3. If decrypted: extract api_key + timestamp                 │
 * │  4. Verify timestamp is within tolerance (5 min)              │
 * │  5. Verify api_key matches the client                         │
 * │                                                               │
 * │  WHY? Even if someone intercepts the header, the value is     │
 * │  different every second (because of the timestamp), so replay │
 * │  is practically impossible.                                   │
 * └────────────────────────────────────────────────────────────────┘
 */
class ApiKeyService
{
    /**
     * Validate either a plain API key or an encrypted one.
     *
     * @param  string  $headerValue  The raw X-API-Token header value
     * @return ApiClient|null
     */
    public static function validate(string $headerValue): ?ApiClient
    {
        $useEncryption = config('security.api_key.encrypted', false);

        if ($useEncryption) {
            return self::validateEncrypted($headerValue);
        }

        return self::validatePlain($headerValue);
    }

    /**
     * Validate a plain (unencrypted) API key.
     */
    public static function validatePlain(string $apiKey): ?ApiClient
    {
        $cacheKey = "security:api_client:{$apiKey}";

        $client = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($apiKey) {
            return ApiClient::where('api_key', $apiKey)
                ->where('is_active', true)
                ->first();
        });

        if (! $client || ! $client->isValid()) {
            // Clear cache if client is expired or invalid
            Cache::forget($cacheKey);

            return null;
        }

        return $client;
    }

    /**
     * Validate an encrypted API key header.
     *
     * The header value is: base64( AES-256-CBC( "api_key|timestamp", secret ) )
     * We try to decrypt with each active client's secret.
     */
    public static function validateEncrypted(string $encryptedHeader): ?ApiClient
    {
        $tolerance = config('security.api_key.encrypted_tolerance_sec', 300);

        // Get all active clients (cached for performance)
        $clients = Cache::remember('security:active_clients', now()->addMinutes(5), function () {
            return ApiClient::where('is_active', true)->get();
        });

        foreach ($clients as $client) {
            $decrypted = self::decrypt($encryptedHeader, $client->secret);

            if ($decrypted === null) {
                continue;
            }

            // Expected format: "api_key|timestamp"
            $parts = explode('|', $decrypted, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$apiKey, $timestamp] = $parts;

            // Verify the api_key matches this client
            if ($apiKey !== $client->api_key) {
                continue;
            }

            // Verify timestamp is within tolerance
            if (abs(time() - (int) $timestamp) > $tolerance) {
                continue;
            }

            // Final check: not expired
            if (! $client->isValid()) {
                return null;
            }

            return $client;
        }

        return null;
    }

    /**
     * Generate a new API key + secret pair with optional expiration.
     *
     * @param  string    $name        Client name (e.g. "Mobile iOS", "Web Dashboard")
     * @param  int|null  $expiryDays  Days until key expires (null = never)
     * @return ApiClient
     */
    public static function createClient(string $name, ?int $expiryDays = null): ApiClient
    {
        $defaultExpiry = $expiryDays ?? config('security.api_key.default_expiry_days');

        return ApiClient::create([
            'name' => $name,
            'api_key' => bin2hex(random_bytes(32)),
            'secret' => bin2hex(random_bytes(32)),
            'is_active' => true,
            'expires_at' => $defaultExpiry ? now()->addDays($defaultExpiry) : null,
            'last_rotated_at' => now(),
        ]);
    }

    /**
     * Rotate (regenerate) a client's API key and secret.
     *
     * The old key immediately becomes invalid.
     *
     * @param  ApiClient  $client     The client to rotate keys for
     * @param  int|null   $expiryDays  New expiry (null = use config default)
     * @return ApiClient  Updated client with new keys
     */
    public static function rotateKeys(ApiClient $client, ?int $expiryDays = null): ApiClient
    {
        $oldKey = $client->api_key;
        $defaultExpiry = $expiryDays ?? config('security.api_key.default_expiry_days');

        $client->update([
            'api_key' => bin2hex(random_bytes(32)),
            'secret' => bin2hex(random_bytes(32)),
            'last_rotated_at' => now(),
            'expires_at' => $defaultExpiry ? now()->addDays($defaultExpiry) : null,
        ]);

        // Clear old cached key
        Cache::forget("security:api_client:{$oldKey}");
        Cache::forget('security:active_clients');

        return $client->fresh();
    }

    /**
     * Renew a client's expiration without changing the keys.
     *
     * @param  ApiClient  $client  The client to renew
     * @param  int        $days    New expiry period in days
     * @return ApiClient
     */
    public static function renewExpiry(ApiClient $client, int $days = 30): ApiClient
    {
        $client->update([
            'expires_at' => now()->addDays($days),
        ]);

        Cache::forget("security:api_client:{$client->api_key}");
        Cache::forget('security:active_clients');

        return $client->fresh();
    }

    /**
     * Encrypt a value using AES-256-CBC.
     *
     * This is used by clients to encrypt the API key header.
     * Provided here for testing & documentation purposes.
     *
     * @param  string  $data    Data to encrypt (e.g. "api_key|timestamp")
     * @param  string  $secret  The client's secret key
     * @return string  Base64-encoded encrypted value
     */
    public static function encrypt(string $data, string $secret): string
    {
        $key = substr(hash('sha256', $secret), 0, 32);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv.$encrypted);
    }

    /**
     * Decrypt a value that was encrypted with AES-256-CBC.
     *
     * @param  string  $encryptedData  Base64-encoded encrypted value
     * @param  string  $secret         The client's secret key
     * @return string|null  Decrypted data, or null if decryption failed
     */
    public static function decrypt(string $encryptedData, string $secret): ?string
    {
        $raw = base64_decode($encryptedData, true);

        if ($raw === false || strlen($raw) < 17) {
            return null;
        }

        $key = substr(hash('sha256', $secret), 0, 32);
        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted === false ? null : $decrypted;
    }
}
