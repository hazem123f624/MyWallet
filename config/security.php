<?php

$version = env('API_VERSION', 'v1');

return [

    /*
    |--------------------------------------------------------------------------
    | Security Framework — Master Switch
    |--------------------------------------------------------------------------
    | When disabled, ALL security middleware will pass through without checks.
    | Useful for local development or debugging.
    */
    'enabled' => env('SECURITY_ENABLED', true),
    'test_key' => env('SECURITY_TEST_KEY', 'Test App'),

    /*
    |--------------------------------------------------------------------------
    | IP Filter
    |--------------------------------------------------------------------------
    | Block known-bad IPs or restrict access to known-good IPs.
    | - If whitelist is NOT empty → only those IPs can access (strict mode).
    | - If whitelist IS empty → all IPs allowed except blacklisted ones.
    */
    'ip_filter' => [
        'enabled' => env('SECURITY_IP_FILTER', true),
        'whitelist' => explode(',', env('SECURITY_IP_WHITELIST', '')),
        'blacklist' => explode(',', env('SECURITY_IP_BLACKLIST', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | Throttle requests per IP to prevent abuse, brute-force, or DDoS.
    */
    'rate_limit' => [
        'enabled' => env('SECURITY_RATE_LIMIT', true),
        'max_attempts' => (int) env('SECURITY_RATE_MAX', 60),
        'decay_minutes' => (int) env('SECURITY_RATE_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Force JSON
    |--------------------------------------------------------------------------
    | Reject requests that don't send Accept: application/json
    | or Content-Type: application/json for POST/PUT/PATCH.
    */
    'force_json' => [
        'enabled' => env('SECURITY_FORCE_JSON', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payload Size Limit
    |--------------------------------------------------------------------------
    | Reject request bodies larger than the specified size.
    | Protects against large payload attacks (e.g. JSON bombs).
    */
    'payload_size' => [
        'enabled' => env('SECURITY_PAYLOAD_SIZE', true),
        'max_kb' => (int) env('SECURITY_MAX_PAYLOAD_KB', 5120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    | Automatically inject standard security headers into every response.
    */
    'security_headers' => [
        'enabled' => env('SECURITY_HEADERS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Protection
    |--------------------------------------------------------------------------
    | Control which origins, methods, and headers are allowed.
    */
    'cors' => [
        'enabled' => env('SECURITY_CORS', true),
        'allowed_origins' => explode(',', env('SECURITY_CORS_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        'allowed_headers' => [
            'Content-Type', 'Authorization', 'X-Requested-With',
            'X-API-Token', 'X-SIGNATURE', 'X-TIMESTAMP',
        ],
        'expose_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
        'max_age' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key Validation
    |--------------------------------------------------------------------------
    | Each client (mobile app, web frontend, third-party) gets a unique
    | API key that must be sent in the X-API-Token header.
    */
    'api_key' => [
        'enabled' => env('SECURITY_API_KEY', true),
        'header_name' => 'X-API-Token',

        // When true, the X-API-Token header must be AES-256-CBC encrypted:
        //   encrypt("api_key|timestamp", secret) → base64
        // This prevents header interception — value changes every second.
        'encrypted' => env('SECURITY_API_KEY_ENCRYPTED', false),

        // Max age (seconds) for the timestamp inside an encrypted header.
        // Prevents replay of captured encrypted headers.
        'encrypted_tolerance_sec' => (int) env('SECURITY_API_KEY_TOLERANCE', 300),

        // Default key expiration in days. Set to null for keys that never expire.
        // Can be overridden per client when creating/rotating keys.
        'default_expiry_days' => env('SECURITY_API_KEY_EXPIRY_DAYS', 30),

        // When key has <= this many days remaining, key-info endpoint
        // returns should_renew: true, telling the client to renew.
        'renew_threshold_days' => (int) env('SECURITY_API_KEY_RENEW_THRESHOLD', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timestamp & Anti-Replay
    |--------------------------------------------------------------------------
    | Require a Unix timestamp in X-TIMESTAMP header.
    | Reject requests older than tolerance_sec to prevent replay attacks.
    */
    'timestamp' => [
        'enabled' => env('SECURITY_TIMESTAMP', false),
        'header_name' => 'X-TIMESTAMP',
        'tolerance_sec' => (int) env('SECURITY_TIMESTAMP_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | HMAC Signature Validation
    |--------------------------------------------------------------------------
    | The client must sign the request using:
    |   signature = HMAC-SHA256(METHOD|URL|TIMESTAMP|BODY, client_secret)
    | and send it in the X-SIGNATURE header.
    */
    'signature' => [
        'enabled' => env('SECURITY_SIGNATURE', false),
        'header_name' => 'X-SIGNATURE',
        'algorithm' => 'sha256',
    ],

    /*
    |--------------------------------------------------------------------------
    | 404 Probing Detection
    |--------------------------------------------------------------------------
    | Track repeated 404 hits per IP. If an IP triggers too many 404s
    | in a short window, auto-block it (likely a scanner/bot).
    */
    'probing_detection' => [
        'enabled' => env('SECURITY_PROBING', false),
        'max_404_per_minute' => (int) env('SECURITY_PROBING_MAX', 10),
        'block_duration_min' => (int) env('SECURITY_PROBING_BLOCK', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enumeration Masking
    |--------------------------------------------------------------------------
    | Return the same 404 message for all "not found" responses
    | to prevent attackers from guessing valid resource IDs.
    */
    'enumeration_masking' => [
        'enabled' => env('SECURITY_ENUM_MASKING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Logging
    |--------------------------------------------------------------------------
    | Log all security events (blocked IPs, failed signatures, etc.)
    | to the security_logs database table.
    */
    'logging' => [
        'enabled' => env('SECURITY_LOGGING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Adaptive Threat Detection
    |--------------------------------------------------------------------------
    | Computes a risk score (0–100) per request by analyzing multiple signals
    | (recent events, user-agent patterns, rate-limit proximity, etc.).
    | High-risk IPs are auto-blocked via cache.
    */
    'threat_detection' => [
        'enabled' => env('SECURITY_THREAT_DETECTION', false),
        'low_threshold' => (int) env('SECURITY_THREAT_LOW', 25),
        'medium_threshold' => (int) env('SECURITY_THREAT_MEDIUM', 50),
        'high_threshold' => (int) env('SECURITY_THREAT_HIGH', 80),
        'auto_block_minutes' => (int) env('SECURITY_THREAT_BLOCK_MIN', 60),
        'bad_ua_patterns' => [
            'sqlmap', 'nikto', 'nmap', 'masscan', 'zgrab',
            'dirbuster', 'gobuster', 'wfuzz', 'hydra', 'burp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nonce Protection (Anti-Replay)
    |--------------------------------------------------------------------------
    | One-time-use tokens stored in Redis. Client fetches a nonce before
    | making a request, then sends it in the X-NONCE header. Each nonce
    | can only be used once, preventing exact replay of captured requests.
    */
    'nonce' => [
        'enabled' => env('SECURITY_NONCE', false),
        'header_name' => 'X-NONCE',
        'ttl_seconds' => (int) env('SECURITY_NONCE_TTL', 300),

        /*
        |--------------------------------------------------------------------------
        | Selective Protection Settings
        |--------------------------------------------------------------------------
        | Define which requests require nonce protection.
        */
        'only_for' => [
            'POST', 'PUT', 'PATCH', 'DELETE'
        ],

        // Path patterns that ALWAYS require nonce (even for GET if specified)
        'critical_routes' => [
            $version.'/security/renew-key',
            'api/'.$version.'/payments/*',
            'api/'.$version.'/orders/*',
            'api/'.$version.'/transfers/*',
        ],

        // Path patterns to EXCLUDE from nonce protection
        'exclude_routes' => [
            'api/'.$version.'/auth/*',
            'api/'.$version.'/login',
            'api/'.$version.'/register',
            'api/'.$version.'/security/nonce', // Must exclude self
        ],
    ],
];
