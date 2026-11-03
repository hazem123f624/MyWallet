<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * OtpService — Generate and verify One-Time Passwords (OTP).
 *
 * ─── How to Use ──────────────────────────────────────────────────────────────
 *
 *  1) Generate OTP for a user:
 *     $otpService = app(OtpService::class);
 *     $code = $otpService->generate($user, 'login');          // returns 4-digit code
 *     $code = $otpService->generate($user, 'forgot_password');
 *
 *  2) Verify OTP:
 *     $result = $otpService->verify(model: $user, code: '1234', type: 'login');
 *     // returns true on success
 *
 *  3) Verify OTP and get Sanctum token (for login flows):
 *     $result = $otpService->verify(model: $user, code: '1234', type: 'login', withToken: true);
 *     // returns ['token' => 'plain-text-token']
 *
 *  4) Cache-based OTP (e.g. for registration where User doesn't exist yet):
 *     $code = $otpService->generateByCache($phone, 'registration', $request->all());
 *     $data = $otpService->verifyByCache($phone, '1234', 'registration'); // returns original $request->all()
 *
 * ─── Security Policy ────────────────────────────────────────────────────────
 *
 *  - OTP expires after configured minutes (default: 5 min)
 *  - Cooldown between resends (default: 60 sec) to prevent spam
 *  - Max failed attempts (default: 3) then OTP is locked
 *  - OTP code is hashed in database (model must have a `code` mutator)
 *  - Each OTP can only be verified once
 *
 * ─── Config (config/otp.php or hardcoded defaults) ──────────────────────────
 *
 *  'expiry_minutes'        => env('OTP_EXPIRY_MINUTES', 5),
 *  'cooldown_seconds'      => env('OTP_COOLDOWN_SECONDS', 60),
 *  'max_attempts'          => env('OTP_MAX_ATTEMPTS', 3),
 *
 * ─── Requirements ───────────────────────────────────────────────────────────
 *
 *  The model (User/Admin) must have:
 *  - otps() relationship (morphMany to Otp model)
 *  - The Otp model must have columns: type, code, attempts, is_verified, expires_at, last_sent_at
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
class OtpService
{
    /** How many minutes until the OTP expires */
    public int $expiredAtPerMin;

    /** Seconds to wait before allowing a new OTP to be sent */
    public int $resendCooldownSeconds;

    /** Max failed verification attempts before locking */
    protected int $maxAttempts;

    public function __construct()
    {
        $this->expiredAtPerMin = config('otp.expiry_minutes', 5);
        $this->resendCooldownSeconds = config('otp.cooldown_seconds', 60);
        $this->maxAttempts = config('otp.max_attempts', 3);
    }

    // ─── Generate (Model Based) ──────────────────────────

    /**
     * Generate a new OTP or resend the existing one.
     *
     * Flow:
     *  1. Check if there's a valid (not expired, not verified) OTP
     *  2. If yes AND cooldown hasn't passed → reject (too soon)
     *  3. If yes AND cooldown passed → regenerate code & reset timer
     *  4. If no valid OTP exists → create a fresh one
     *
     * @param  mixed  $model       The user/admin model (must have otps() relationship)
     * @param  string $type        OTP purpose: 'login', 'forgot_password', etc.
     * @param  string|null $identifier The email or phone to send to.
     * @return int    The 6-digit OTP code (to be sent via email/sms)
     */
    public function generate($model, string $type = 'login', ?string $identifier = null): int
    {
        $existingOtp = $model->otps()
            ->where('type', $type)
            ->where('is_verified', false)
            ->latest()
            ->first();

        // Has a valid, non-expired OTP?
        if ($existingOtp && $existingOtp->expires_at->isFuture()) {
            // If locked due to too many failed attempts, allow regeneration on resend
            if ($existingOtp->attempts >= $this->maxAttempts) {
                return $this->updateOtp($existingOtp, $identifier);
            }

            // Cooldown check — prevent spam
            if ($existingOtp->last_sent_at && $existingOtp->last_sent_at->addSeconds($this->resendCooldownSeconds)->isFuture()) {
                throw ValidationException::withMessages([
                    'otp' => [__('OTP already sent. Please wait before requesting again.')],
                ]);
            }

            // Cooldown passed — regenerate the code
            return $this->updateOtp($existingOtp, $identifier);
        }

        // No valid OTP — create a new one
        return $this->createOtp($model, $type, $identifier);
    }

    /**
     * Create a brand-new OTP record.
     */
    protected function createOtp($model, string $type, ?string $identifier = null): int
    {
        $code = config('app.env' == 'production') ? random_int(1000, 9999) : 1234;

        if (! $identifier) {
            $identifier = $model->email ?? $model->phone ?? $model->identifier;
        }

        $model->otps()->create([
            'identifier'   => $identifier,
            'type'         => $type,
            'code'         => $code,       // hashed automatically by Otp model mutator
            'attempts'     => 0,
            'is_verified'  => false,
            'expires_at'   => Carbon::now()->addMinutes($this->expiredAtPerMin),
            'last_sent_at' => Carbon::now(),
        ]);

        return $code;
    }

    /**
     * Regenerate the code for an existing OTP (resend scenario).
     * Resets attempts and extends expiry.
     */
    protected function updateOtp($otp, ?string $identifier = null): int
    {
        $code = config('app.env' == 'production') ? random_int(1000, 9999) : 1234;

        $updateData = [
            'code'         => $code,       // hashed automatically by Otp model mutator
            'attempts'     => 0,
            'expires_at'   => Carbon::now()->addMinutes($this->expiredAtPerMin),
            'last_sent_at' => Carbon::now(),
        ];

        if ($identifier) {
            $updateData['identifier'] = $identifier;
        }

        $otp->update($updateData);

        return $code;
    }

    // ─── Generate (Cache Based) ──────────────────────────

    /**
     * Generate an OTP stored in cache instead of database.
     * Useful for registration flows where the User model doesn't exist yet.
     *
     * @param string $identifier   The phone or email.
     * @param string $type         The purpose (e.g. 'registration').
     * @param array $extraData     Any data to persist (e.g. registration payload).
     * @return int                 The 6-digit code.
     */
    public function generateByCache(string $identifier, string $type = 'registration', array $extraData = []): int
    {
        $cacheKey = "otp:cache:{$type}:{$identifier}";
        $existing = Cache::get($cacheKey);

        $cachedData = $extraData !== [] ? $extraData : ($existing['data'] ?? []);
        // A code locked by too many failed attempts is replaced straight away
        // (attempts reset); otherwise a cooldown applies before resending.
        $locked = $existing && ($existing['attempts'] ?? 0) >= $this->maxAttempts;

        if (! $locked && $existing && isset($existing['last_sent_at'])) {
            $lastSentAt = Carbon::parse($existing['last_sent_at']);
            if ($lastSentAt->addSeconds($this->resendCooldownSeconds)->isFuture()) {
                throw ValidationException::withMessages([
                    'otp' => [__('OTP already sent. Please wait before requesting again.')],
                ]);
            }
        }

        $code = config('app.env' == 'production') ? random_int(1000, 9999) : 1234;

        Cache::put($cacheKey, [
            'code'         => Hash::make($code),
            'attempts'     => 0,
            'data'         => $cachedData,
            'last_sent_at' => Carbon::now()->toDateTimeString(),
        ], Carbon::now()->addMinutes($this->expiredAtPerMin));

        return $code;
    }

    // ─── Verify (Model Based) ────────────────────────────

    /**
     * Verify an OTP code submitted by the user.
     *
     * @param  mixed  $model     The user/admin model
     * @param  string $code      The 6-digit code submitted by user
     * @param  string $type      OTP purpose: 'login', 'forgot_password', etc.
     * @param  bool   $withToken If true, returns a Sanctum token after verification
     * @return bool|array        true on success, or ['token' => '...'] if withToken
     */
    public function verify($model, string $code, string $type = 'login', $withToken = false): bool|array
    {
        $otp = $model->otps()->where('type', $type)->latest()->first();

        // ① Does it exist?
        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => [__('Invalid OTP.')],
            ]);
        }

        // ② Already verified? (prevent replay)
        if ($otp->is_verified) {
            throw ValidationException::withMessages([
                'otp' => [__('Your OTP is already verified.')],
            ]);
        }

        // ③ Expired?
        if ($otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => [__('Expired OTP. Please request a new one.')],
            ]);
        }

        // ④ Too many failed attempts? (brute force protection)
        if ($otp->attempts >= $this->maxAttempts) {
            throw ValidationException::withMessages([
                'otp' => [__('Too many failed attempts. Please request a new OTP.')],
            ]);
        }

        // ⑤ Is the code correct?
        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            $remaining = $this->maxAttempts - $otp->attempts;
            throw ValidationException::withMessages([
                'otp' => [__("Invalid OTP. :count attempts remaining.", ['count' => $remaining])],
            ]);
        }

        // ⑥ All passed — mark as verified
        $otp->update(['is_verified' => true]);

        if ($withToken) {
            $token = $model->createToken('otp-token')->plainTextToken;

            return [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => null,
            ];
        }

        return true;
    }

    // ─── Verify (Cache Based) ────────────────────────────

    /**
     * Verify a cache-based OTP.
     * Returns the associated data if valid.
     *
     * @param string $identifier
     * @param string $code
     * @param string $type
     * @return array The original data passed during generation.
     */
    public function verifyByCache(string $identifier, string $code, string $type = 'registration'): array
    {
        $cacheKey = "otp:cache:{$type}:{$identifier}";
        $cached   = Cache::get($cacheKey);
        // ① Does it exist?
        if (! $cached) {
            throw ValidationException::withMessages([
                'otp' => [__('Invalid or expired OTP.')],
            ]);
        }

        // ② Too many failed attempts?
        if ($cached['attempts'] >= $this->maxAttempts) {
            throw ValidationException::withMessages([
                'otp' => [__('Too many failed attempts. Please request a new OTP.')],
            ]);
        }

        // ③ Is the code correct?
        if (! Hash::check($code, $cached['code'])) {
            $cached['attempts']++;
            Cache::put($cacheKey, $cached, Carbon::now()->addMinutes($this->expiredAtPerMin));

            $remaining = $this->maxAttempts - $cached['attempts'];
            throw ValidationException::withMessages([
                'otp' => [__("Invalid OTP. :count attempts remaining.", ['count' => $remaining])],
            ]);
        }

        // ④ All passed — delete from cache and return data
        Cache::forget($cacheKey);

        return $cached['data'] ?? [];
    }
}
