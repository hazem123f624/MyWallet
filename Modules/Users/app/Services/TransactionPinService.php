<?php

namespace Modules\Users\Services;

use App\Services\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Users\Models\User;

class TransactionPinService
{
    private const ERROR_NOT_SET = 'not_set';

    private const ERROR_INCORRECT = 'incorrect';

    private const ERROR_LOCKED = 'locked';

    public function __construct(private OtpService $otpService) {}

    public function hasPin(User $user): bool
    {
        return ! is_null($user->transaction_pin) && $user->transaction_pin !== '';
    }

    public function set(User $user, string $pin): void
    {
        DB::transaction(function () use ($user, $pin): void {
            $lockedUser = $this->lockUser($user);

            if ($this->hasPin($lockedUser)) {
                throw ValidationException::withMessages([
                    'transaction_pin' => [__('users::messages.transaction_pin_already_set')],
                ]);
            }

            $lockedUser->update([
                'transaction_pin' => $pin,
                'transaction_pin_failed_attempts' => 0,
                'transaction_pin_locked_until' => null,
            ]);
        });
    }

    public function change(User $user, string $currentPin, string $newPin): void
    {
        $error = DB::transaction(function () use ($user, $currentPin, $newPin): ?string {
            $lockedUser = $this->lockUser($user);
            $error = $this->checkPin($lockedUser, $currentPin);

            if ($error !== null) {
                return $error;
            }

            $lockedUser->update([
                'transaction_pin' => $newPin,
                'transaction_pin_failed_attempts' => 0,
                'transaction_pin_locked_until' => null,
            ]);

            return null;
        });

        if ($error !== null) {
            $this->throwPinError($error);
        }
    }

    public function verify(User $user, string $pin): bool
    {
        $error = DB::transaction(function () use ($user, $pin): ?string {
            return $this->checkPin($this->lockUser($user), $pin);
        });

        if ($error !== null) {
            $this->throwPinError($error);
        }

        return true;
    }

    public function sendResetOtp(User $user): int
    {
        $this->assertHasPin($user);

        return $this->otpService->generate($user, 'transaction_pin_reset');
    }

    public function resetWithOtp(User $user, string $otp, string $pin): void
    {
        $this->assertHasPin($user);
        $this->otpService->verify($user, $otp, 'transaction_pin_reset');

        DB::transaction(function () use ($user, $pin): void {
            $this->lockUser($user)->update([
                'transaction_pin' => $pin,
                'transaction_pin_failed_attempts' => 0,
                'transaction_pin_locked_until' => null,
            ]);
        });
    }

    private function checkPin(User $user, string $pin): ?string
    {
        if (! $this->hasPin($user)) {
            return self::ERROR_NOT_SET;
        }

        if ($user->transaction_pin_locked_until?->isFuture()) {
            return self::ERROR_LOCKED;
        }

        if ($user->transaction_pin_locked_until?->isPast()) {
            $user->update([
                'transaction_pin_failed_attempts' => 0,
                'transaction_pin_locked_until' => null,
            ]);
        }

        if (! Hash::check($pin, $user->transaction_pin)) {
            $attempts = (int) $user->transaction_pin_failed_attempts + 1;
            $updates = ['transaction_pin_failed_attempts' => $attempts];
            $maxAttempts = max(1, (int) config('transaction_pin.max_attempts', 5));

            if ($attempts >= $maxAttempts) {
                $updates['transaction_pin_locked_until'] = now()->addMinutes(
                    max(1, (int) config('transaction_pin.lock_minutes', 15))
                );
            }

            $user->update($updates);

            return isset($updates['transaction_pin_locked_until'])
                ? self::ERROR_LOCKED
                : self::ERROR_INCORRECT;
        }

        $user->update([
            'transaction_pin_failed_attempts' => 0,
            'transaction_pin_locked_until' => null,
        ]);

        return null;
    }

    private function assertHasPin(User $user): void
    {
        if (! $this->hasPin($user)) {
            throw ValidationException::withMessages([
                'transaction_pin' => [__('users::messages.transaction_pin_not_set')],
            ]);
        }
    }

    private function lockUser(User $user): User
    {
        return User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
    }

    private function throwPinError(string $error): never
    {
        $message = match ($error) {
            self::ERROR_NOT_SET => __('users::messages.transaction_pin_not_set'),
            self::ERROR_LOCKED => __('users::messages.transaction_pin_locked'),
            default => __('users::messages.transaction_pin_incorrect'),
        };

        throw ValidationException::withMessages([
            'transaction_pin' => [$message],
        ]);
    }
}
