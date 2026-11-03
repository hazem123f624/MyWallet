<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Expiry (minutes)
    |--------------------------------------------------------------------------
    |
    | How many minutes until the OTP code expires.
    | After this time, the user must request a new OTP.
    |
    */
    'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Resend Cooldown (seconds)
    |--------------------------------------------------------------------------
    |
    | Minimum seconds to wait before the user can request a new OTP.
    | Prevents spamming the SMS/Email provider.
    |
    */
    'cooldown_seconds' => env('OTP_COOLDOWN_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Max Verification Attempts
    |--------------------------------------------------------------------------
    |
    | After this many failed verification attempts, the OTP becomes invalid
    | and the user must request a new one. Protects against brute force.
    |
    */
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),

];
