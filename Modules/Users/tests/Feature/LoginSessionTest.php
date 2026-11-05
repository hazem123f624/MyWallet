<?php

namespace Modules\Users\Tests\Feature;

use Modules\Users\Tests\Support\UsersApiTestCase;

class LoginSessionTest extends UsersApiTestCase
{
    public function test_login_returns_token_and_resets_failed_attempts(): void
    {
        $user = $this->createUser([
            'failed_login_attempts' => 3,
            'locked_until' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/v1/mobile/users/login', $this->loginPayload());

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'phone', 'token']]);

        $user->refresh();

        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertNotNull($user->last_login_at);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_rejects_an_unverified_account(): void
    {
        $this->createUser(['phone_verified_at' => null]);

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload())
            ->assertForbidden()
            ->assertJsonPath('message', __('users::messages.verify_account_first'));
    }

    public function test_login_rejects_a_suspended_account_with_a_specific_message(): void
    {
        $this->createUser(['is_active' => false]);

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload())
            ->assertForbidden()
            ->assertJsonPath('message', __('users::messages.account_suspended'));
    }

    public function test_suspended_account_cannot_use_an_existing_token(): void
    {
        $user = $this->createUser(['is_active' => false]);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertForbidden()
            ->assertJsonPath('message', __('users::messages.account_suspended'));
    }

    public function test_login_locks_after_five_failed_attempts(): void
    {
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/mobile/users/login', $this->loginPayload(
                password: 'wrong-password'
            ))->assertUnprocessable();
        }

        $user->refresh();

        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        $response = $this->postJson('/api/v1/mobile/users/login', $this->loginPayload());
        $response->assertUnprocessable();
        $this->assertStringContainsString('Too many login attempts', $response->json('message'));
    }

    public function test_login_applies_the_configured_lock_durations(): void
    {
        $cases = [
            4 => 15,
            9 => 60,
            14 => 1440,
        ];

        foreach ($cases as $failedAttempts => $expectedMinutes) {
            $digits = '010111122'.str_pad((string) $failedAttempts, 2, '0', STR_PAD_LEFT);
            $user = $this->createUser([
                'email' => 'lock-'.$failedAttempts.'@example.com',
                'phone' => '+20'.substr($digits, 1),
                'failed_login_attempts' => $failedAttempts,
                'locked_until' => null,
            ]);

            $this->postJson('/api/v1/mobile/users/login', $this->loginPayload(
                phone: $digits,
                password: 'wrong-password'
            ))->assertUnprocessable();

            $user->refresh();

            $this->assertNotNull($user->locked_until);
            $this->assertTrue(
                $user->locked_until->between(
                    now()->addMinutes($expectedMinutes - 1),
                    now()->addMinutes($expectedMinutes + 1)
                )
            );
        }
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/logout')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertUnauthorized();
    }

    public function test_refresh_token_revokes_the_old_token_and_issues_a_new_one(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $response = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/refresh-token');

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => ['token']]);

        $newToken = $response->json('data.token');

        $this->assertNotSame($token, $newToken);
        $this->assertSame(1, $user->tokens()->count());

        $this->withHeaders($this->bearer($newToken))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertOk();

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertUnauthorized();
    }

    public function test_login_with_wrong_password_increments_failed_attempts(): void
    {
        $user = $this->createUser();

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload(
            password: 'wrong-password'
        ))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('auth.invalid_credentials'));

        $this->assertSame(1, $user->fresh()->failed_login_attempts);
    }

    public function test_login_rejects_an_unknown_phone(): void
    {
        $this->createCountry();

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload(
            phone: '01099998888'
        ))
            ->assertUnprocessable()
            ->assertJsonPath('message', __('auth.invalid_credentials'));
    }

    public function test_protected_route_requires_a_token(): void
    {
        $this->getJson('/api/v1/mobile/users/profile')
            ->assertUnauthorized();
    }
}
