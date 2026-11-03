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

class PassportRepository implements AuthInterface
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
        $request['is_active'] = ActiveStatus::Active;
        $guard = $this->guardData['guard'] ?? null;
        if (! $guard) {
            throw new \Exception('Guard not defined.');
        }
        if (! Auth::guard($guard)->attempt($request)) {
            throw ValidationException::withMessages(['email' => [__('Invalid credentials.')]]);
        }

        $authUser = Auth::guard($guard)->user();
        $dtoClass = $this->guardData['dtoClass'];

        if (! $createToken) {
            return [
                'user' => $dtoClass::fromModel($authUser),
            ];
        }

        $tokenResult = $authUser->createToken($guard.'-token');

        return [
            'user' => $dtoClass::fromModel($authUser),
            'token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at->toDateTimeString(),
        ];
    }

    public function logout($ids = []): void
    {
        if (!empty($ids)) {
            $users = $this->guardData['model']::whereIn('id',$ids)->get();
            foreach ($users as $user) {
                $user->token()->revoke();
            }
        }else{
            $user = auth()->guard($this->guardData['guard'])->user();
            if ($user) {
                $user->token()?->revoke();
            }
        }

    }

    public function refreshToken(): array
    {
        $guard = $this->guardData['guard'];
        $user = auth()->guard($guard)->user();

        if (! $user) {
            throw ValidationException::withMessages(['token' => [__('Unauthenticated.')]]);
        }

        // Passport: revoke old token and create a new one
        $user->token()?->revoke();
        $tokenResult = $user->createToken($guard.'-token');
        $dtoClass = $this->guardData['dtoClass'];

        return [
            'user' => $dtoClass::fromModel($user),
            'token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at->toDateTimeString(),
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
