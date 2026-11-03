<?php

namespace App\Services\Authentication\types;

use App\Services\Authentication\AuthInterface;
use App\Services\Messages\MessageFactory;
use App\Services\OtpService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Admins\Enums\ActiveStatus;

class SanctumRepository implements AuthInterface
{
    public array $guardData;

    public OtpService $otpService;

    public int $changePasswordTokenExpiredAtAfterPerMinutes;

    public function __construct($guardData)
    {
        $this->guardData = $guardData;
        $this->otpService = app(OtpService::class);
        $this->changePasswordTokenExpiredAtAfterPerMinutes = 10;
    }

    public function login($request, $createToken = true): array
    {
        $request['is_active'] = ActiveStatus::Active->value;
        $guard = $this->guardData['guard'] ?? null;
        if (! $guard) {
            throw new \Exception('Guard not defined.');
        }
        $field = array_key_exists('email', $request) ? 'email' : 'phone';
        $this->checkDatabaseLock($field, $request[$field]);
        if (! Auth::guard($guard)->attempt($request)) {
            $this->handleFailedLogin($field, $request[$field]);
            throw ValidationException::withMessages([
                'credentials' => [__('auth.invalid_credentials')],
            ]);
        }
        $authUser = Auth::guard($guard)->user();
        $authUser->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);
        $dtoClass = $this->guardData['dtoClass'];
        if (! $createToken) {
            return [
                'user' => $dtoClass::fromModel($authUser),
            ];
        }

        $token = $authUser->createToken($guard . '-token', [$guard])->plainTextToken;
        return [
            'user' => $dtoClass::fromModel($authUser),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration')
                ? config('sanctum.expiration') * 60
                : null,
        ];
    }
    private function checkDatabaseLock(string $field, string $value): void
    {
        $model = $this->guardData['model'];

        $user = $model::where($field, $value)->first();

        if (! $user) {
            return;
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            $seconds = now()->diffInSeconds($user->locked_until);

            if ($seconds >= 23 * 3600) {
                $remaining = '1 day';
            } else {
                $remaining = $user->locked_until->diffForHumans(['parts' => 1, 'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,]);
            }
            throw ValidationException::withMessages([
                'credentials' => [
                    __("Too many login attempts. Please try again after :remaining .", [

                        'remaining' => $remaining,
                    ]),
                ],
            ]);
        }

    }

    private function handleFailedLogin(string $field, string $value): void
    {
        $model = $this->guardData['model'];

        $user = $model::where($field, $value)->first();

        if (! $user) {
            return;
        }

        $user->increment('failed_login_attempts');
        $user->refresh();

        $attempts = $user->failed_login_attempts;

        if ($attempts >= 15) {
            $lockedUntil = now()->addDay();
        } elseif ($attempts == 10) {
            $lockedUntil = now()->addHour();
        } elseif ($attempts == 5) {
            $lockedUntil = now()->addMinutes(15);
        } else {
            return;
        }

        $user->update([
            'locked_until' => $lockedUntil,
        ]);
    }
    public function logout($ids = []): void
    {
        if (!empty($ids)) {
            $users = $this->guardData['model']::whereIn('id',$ids)->get();
            foreach ($users as $user) {
                $user->tokens()->delete();
            }
        }else{
            $user = auth()->guard($this->guardData['guard'])->user() ?? auth('sanctum')->user();
            if ($user && method_exists($user, 'currentAccessToken')) {
                $user->currentAccessToken()?->delete();
            }
        }
    }

    public function refreshToken(): array
    {
        $guard = $this->guardData['guard'];
        $user = auth()->guard($guard)->user() ?? auth('sanctum')->user();

        if (! $user) {
            throw ValidationException::withMessages(['token' => [__('Unauthenticated.')]]);
        }

        // Sanctum: delete old token and create a new one
        $user->currentAccessToken()?->delete();
        $token = $user->createToken($guard.'-token', [$guard])->plainTextToken;
        $dtoClass = $this->guardData['dtoClass'];

        return [
            'user' => $dtoClass::fromModel($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null,
        ];
    }

    public function forgotPassword($request, $type = 'email'): string
    {
        $modelClass = $this->guardData['model'];
        if ($type == 'phone')
            return $this->forgotPasswordPerSMS($request['phone'], $modelClass);

        return $this->forgotPasswordPerEmail($request['email'], $modelClass);
    }

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
    public function resetPassword($request, $type = 'email'):void
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
        $user = $this->guardData['model']::where($type, $request[$type])->where('change_password_token', $request['change_password_token'])->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }
        if ($user->change_password_expired_at->isPast()) {
            throw ValidationException::withMessages([
                'change_password_token' => [__('Expired Token.')],
            ]);
        }
        if (isset($request['otp'])) {
            $otp = $user->otps()->where('type', 'forgot_password')->where('is_verified', true)->first();
            if (! $otp || ! Hash::check($request['otp'], $otp->code)) {
                throw ValidationException::withMessages([
                    'token' => [__('Invalid OTP.')],
                ]);
            }
        }

        return $user;
    }
}
