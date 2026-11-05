<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\Users\Tests\Support\UsersApiTestCase;

class TransactionPinTest extends UsersApiTestCase
{
    public function test_authenticated_user_can_set_the_first_transaction_pin(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin', [
                'transaction_pin' => '2468',
                'transaction_pin_confirmation' => '2468',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('users::messages.transaction_pin_set'))
            ->assertJsonMissingPath('data.transaction_pin');

        $user->refresh();

        $this->assertTrue(Hash::check('2468', $user->transaction_pin));
        $this->assertNotSame('2468', $user->transaction_pin);
        $this->assertSame(0, $user->transaction_pin_failed_attempts);
        $this->assertNull($user->transaction_pin_locked_until);
    }

    public function test_transaction_pin_can_only_be_set_once(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin', [
                'transaction_pin' => '1357',
                'transaction_pin_confirmation' => '1357',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users::messages.transaction_pin_already_set'));
    }

    public function test_transaction_pin_confirmation_must_match(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin', [
                'transaction_pin' => '2468',
                'transaction_pin_confirmation' => '1357',
            ])
            ->assertUnprocessable();
    }

    public function test_transaction_pin_rejects_malformed_and_predictable_values(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        foreach (['123', '12345', '12a4', '1111', '1234', '9876'] as $pin) {
            $this->withHeaders($this->bearer($token))
                ->postJson('/api/v1/mobile/users/transaction-pin', [
                    'transaction_pin' => $pin,
                    'transaction_pin_confirmation' => $pin,
                ])
                ->assertUnprocessable();
        }

        $this->assertNull($user->fresh()->transaction_pin);
    }

    public function test_authenticated_user_can_change_the_transaction_pin(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/change', [
                'current_transaction_pin' => '2468',
                'transaction_pin' => '1357',
                'transaction_pin_confirmation' => '1357',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('users::messages.transaction_pin_changed'));

        $user->refresh();

        $this->assertTrue(Hash::check('1357', $user->transaction_pin));
        $this->assertFalse(Hash::check('2468', $user->transaction_pin));
        $this->assertSame(0, $user->transaction_pin_failed_attempts);
    }

    public function test_changing_the_transaction_pin_requires_the_current_pin(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/change', [
                'current_transaction_pin' => '0000',
                'transaction_pin' => '1357',
                'transaction_pin_confirmation' => '1357',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users::messages.transaction_pin_incorrect'));

        $user->refresh();

        $this->assertTrue(Hash::check('2468', $user->transaction_pin));
        $this->assertSame(1, $user->transaction_pin_failed_attempts);
    }

    public function test_transaction_pin_can_be_reset_with_an_otp(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);

        $forgot = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/forget')
            ->assertOk()
            ->assertJsonPath('data.otp', 1234);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/reset', [
                'otp' => '1234',
                'transaction_pin' => '1357',
                'transaction_pin_confirmation' => '1357',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('users::messages.transaction_pin_reset'));

        $user->refresh();

        $this->assertTrue(Hash::check('1357', $user->transaction_pin));
        $this->assertSame(0, $user->transaction_pin_failed_attempts);
        $this->assertNull($user->transaction_pin_locked_until);
    }

    public function test_transaction_pin_can_be_verified(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/verify', [
                'transaction_pin' => '2468',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('users::messages.transaction_pin_verified'));
    }

    public function test_wrong_transaction_pin_increments_failed_attempts(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/verify', [
                'transaction_pin' => '0000',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users::messages.transaction_pin_incorrect'));

        $this->assertSame(1, $user->fresh()->transaction_pin_failed_attempts);
    }

    public function test_transaction_pin_locks_after_repeated_failures_until_reset(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);
        $maxAttempts = (int) config('transaction_pin.max_attempts');

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->withHeaders($this->bearer($token))
                ->postJson('/api/v1/mobile/users/transaction-pin/verify', [
                    'transaction_pin' => '0000',
                ])
                ->assertUnprocessable();
        }

        $user->refresh();

        $this->assertSame($maxAttempts, $user->transaction_pin_failed_attempts);
        $this->assertNotNull($user->transaction_pin_locked_until);
        $this->assertTrue($user->transaction_pin_locked_until->isFuture());

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/verify', [
                'transaction_pin' => '2468',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users::messages.transaction_pin_locked'));

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/forget')
            ->assertOk();

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/reset', [
                'otp' => '1234',
                'transaction_pin' => '1357',
                'transaction_pin_confirmation' => '1357',
            ])
            ->assertOk();

        $user->refresh();

        $this->assertTrue(Hash::check('1357', $user->transaction_pin));
        $this->assertSame(0, $user->transaction_pin_failed_attempts);
        $this->assertNull($user->transaction_pin_locked_until);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/verify', [
                'transaction_pin' => '1357',
            ])
            ->assertOk();
    }

    public function test_transaction_pin_verification_requires_a_pin_to_be_set(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/transaction-pin/verify', [
                'transaction_pin' => '2468',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users::messages.transaction_pin_not_set'));
    }
}
