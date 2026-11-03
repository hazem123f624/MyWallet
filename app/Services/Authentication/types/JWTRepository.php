<?php

namespace App\Services\Authentication\types;

use App\Services\Authentication\AuthInterface;
use App\Services\Messages\MessageFactory;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Admins\Enums\ActiveStatus;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * JWT Authentication Repository
 *
 * Implements AuthInterface using the tymon/jwt-auth package.
 * JWT tokens are stateless and stored client-side; the server validates them
 * using a secret key configured in `config/jwt.php`.
 *
 * Requirements:
 *   - Package: tymon/jwt-auth
 *   - The User model must implement `Tymon\JWTAuth\Contracts\JWTSubject`
 *   - Guard must be configured as 'jwt' driver in `config/auth.php`
 *
 * Usage:
 *   $auth = AuthFactory::make('jwt', 'admins');
 *   $result = $auth->login(['email' => 'admin@app.com', 'password' => 'secret']);
 *   // Response: ['user' => AdminsDTO, 'token' => 'eyJ...', 'token_type' => 'bearer', 'expires_in' => 3600]
 *
 * @see \App\Services\Authentication\AuthInterface
 * @see \App\Services\Authentication\AuthFactory
 */
class JWTRepository implements AuthInterface
{
    /** @var array Guard configuration data from config('auth.guards.types.{guard}') */
    public array $guardData;

    /** @var OtpService Service for generating and verifying OTPs */
    public OtpService $otpService;

    /** @var int Minutes before the change_password_token expires (default: 10) */
    public int $changePasswordTokenExpiredAtAfterPerMinutes;

    /**
     * @param  array  $guardData  Guard config array containing 'guard', 'model', and 'dtoClass' keys
     */
    public function __construct($guardData)
    {
        $this->guardData = $guardData;
        $this->otpService = app(OtpService::class);
        $this->changePasswordTokenExpiredAtAfterPerMinutes = 10;
    }

    /**
     * Authenticate user via JWT and return a bearer token.
     *
     * Auth::guard()->attempt() returns the JWT token string directly on success.
     * The token expiry is determined by the TTL configured in `config/jwt.php`.
     *
     * Usage:
     *   $auth = AuthFactory::make('jwt', 'admins');
     *
     *   // With token:
     *   $result = $auth->login(['email' => 'admin@app.com', 'password' => 'secret']);
     *   // ['user' => AdminsDTO, 'token' => 'eyJ...', 'token_type' => 'bearer', 'expires_in' => 3600]
     *
     *   // Without token:
     *   $result = $auth->login(['email' => 'admin@app.com', 'password' => 'secret'], false);
     *   // ['user' => AdminsDTO]
     *
     * @param  array  $request     Credentials ['email' => '...', 'password' => '...']
     * @param  bool   $createToken Whether to include the JWT token in response (default: true)
     * @return array  User data with optional token information
     *
     * @throws \Exception If guard is not defined
     * @throws ValidationException If credentials are invalid
     */
    public function login($request, $createToken = true): array
    {
        $request['is_active'] = ActiveStatus::Active;
        $guard = $this->guardData['guard'] ?? null;
        if (! $guard) {
            throw new \Exception('Guard not defined.');
        }

        $token = Auth::guard($guard)->attempt($request);
        if (! $token) {
            throw ValidationException::withMessages(['email' => [__('Invalid credentials.')]]);
        }

        $authUser = Auth::guard($guard)->user();
        $dtoClass = $this->guardData['dtoClass'];

        if (! $createToken) {
            return [
                'user' => $dtoClass::fromModel($authUser),
            ];
        }

        return [
            'user' => $dtoClass::fromModel($authUser),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard($guard)->factory()->getTTL() * 60,
        ];
    }

    /**
     * Logout the user by invalidating the current JWT token.
     *
     * Calls Auth::guard()->logout() which invalidates the token
     * and adds it to the blacklist (if blacklist is enabled in jwt config).
     *
     * @return void
     */
    public function logout($ids = []): void
    {
        $guard = $this->guardData['guard'];
        Auth::guard($guard)->logout();
    }

    /**
     * Refresh the JWT token.
     *
     * Generates a new JWT token from the current one. The old token
     * is invalidated and a fresh token is returned.
     *
     * Usage:
     *   // Send the current token in the Authorization header, then:
     *   $result = $auth->refreshToken();
     *   // ['user' => AdminsDTO, 'token' => 'new-eyJ...', 'token_type' => 'bearer', 'expires_in' => 3600]
     *
     * @return array ['user' => DTO, 'token' => string, 'token_type' => 'bearer', 'expires_in' => int]
     *
     * @throws ValidationException If the token cannot be refreshed (e.g. expired refresh window)
     */
    public function refreshToken(): array
    {
        $guard = $this->guardData['guard'];

        try {
            $token = Auth::guard($guard)->refresh();
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['token' => [__('Token cannot be refreshed, please login again.')]]);
        }

        $authUser = Auth::guard($guard)->user();
        $dtoClass = $this->guardData['dtoClass'];

        return [
            'user' => $dtoClass::fromModel($authUser),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard($guard)->factory()->getTTL() * 60,
        ];
    }

    /**
     * Initiate forgot password flow — sends OTP via email or SMS.
     *
     * @param  array   $request  Must contain 'email' or 'phone' key
     * @param  string  $type     Channel: 'email' or 'phone'
     * @return string  The change_password_token (valid for 10 minutes)
     *
     * @throws ValidationException If user is not found
     */
    public function forgotPassword($request, $type = 'email'): string
    {
        $modelClass = $this->guardData['model'];
        if ($type == 'phone')
            return $this->forgotPasswordPerSMS($request['phone'], $modelClass);

        return $this->forgotPasswordPerEmail($request['email'], $modelClass);
    }

    /**
     * Send password reset OTP via email.
     *
     * Looks up the user by email, generates an OTP, sends it using the
     * MessageFactory email driver, and returns a change_password_token.
     *
     * @param  string  $email       The user's email address
     * @param  string  $modelClass  The Eloquent model class name
     * @return string  The generated change_password_token
     *
     * @throws ValidationException If user is not found
     */
    private function forgotPasswordPerEmail($email, $modelClass): string
    {
        $user = $modelClass::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => ['User not found.']]);
        }
        $otp = app(OtpService::class)->generate($user, 'forgot_password');
        $service = MessageFactory::make('email');
        $service->send([
            'to' => $email,
            'subject' => 'Password Reset',
            'view' => 'emails.reset-password',
            'body' => ['otp' => $otp, 'user' => $user],
        ]);

        return $this->forgetPasswordToken($user);
    }

    /**
     * Send password reset OTP via SMS.
     *
     * Looks up the user by phone number, generates an OTP, sends it using the
     * MessageFactory SMS driver, and returns a change_password_token.
     *
     * @param  string  $phone       The user's phone number
     * @param  string  $modelClass  The Eloquent model class name
     * @return string  The generated change_password_token
     *
     * @throws ValidationException If user is not found
     */
    private function forgotPasswordPerSMS($phone, $modelClass): string
    {
        $user = $modelClass::where('phone', $phone)->first();
        if (! $user) {
            throw ValidationException::withMessages(['phone' => ['User not found.']]);
        }
        $otp = $this->otpService->generate($user, 'forgot_password');
        $service = MessageFactory::make('sms');
        $service->send([
            'to' => $phone,
            'body' => 'Hello '.$user->name.', Your OTP is '.$otp,
        ]);

        return $this->forgetPasswordToken($user);
    }

    /**
     * Generate and store a temporary change_password_token on the user model.
     *
     * The token is a random 64-character string with an expiration time
     * of {@see $changePasswordTokenExpiredAtAfterPerMinutes} minutes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user  The user model instance
     * @return string  The generated token
     */
    private function forgetPasswordToken($user): string
    {
        $changePasswordTokenExpiredAt = Carbon::now()->addMinutes($this->changePasswordTokenExpiredAtAfterPerMinutes);
        $changePasswordToken = (config('app.env') == 'production') ? str()->random(64) : config('auth_guards.test.token');
        $user->update(
            [
                'change_password_token' => $changePasswordToken,
                'change_password_expired_at' => $changePasswordTokenExpiredAt,
            ]
        );

        return $changePasswordToken;
    }

    /**
     * Reset the user's password after OTP verification.
     *
     * @param  array   $request  Must contain: identifier (email/phone), 'token', 'otp', 'password'
     * @param  string  $type     Identifier type: 'email' or 'phone'
     * @return void
     *
     * @throws ValidationException If user not found, token expired, or OTP invalid
     */
    public function resetPassword($request, $type = 'email'): void
    {
        $user = $this->checkResetPasswordToken($request,$type);
        $user->update([
            'password' => $request['password'],
            'change_password_token' => null,
            'change_password_expired_at' => null,
        ]);
    }

    private function checkResetPasswordToken($request,$type)
    {

        $user = $this->guardData['model']::where($type, $request[$type])->where('change_password_token', $request['token'])->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }
        if ($user->change_password_expired_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => [__('Expired Token.')],
            ]);
        }
        $otp = $user->otps()->where('type', 'forgot_password')->where('is_verified', true)->first();
        if (! $otp || ! Hash::check($request['otp'], $otp->code)) {
            throw ValidationException::withMessages([
                'token' => [__('Invalid OTP.')],
            ]);
        }
        return $user;
    }
}
