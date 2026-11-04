<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\Users\Tests\Support\UsersApiTestCase;

class PasswordManagementTest extends UsersApiTestCase
{
    public function test_authenticated_user_can_change_password(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/change-password', [
                'old_password' => 'P@ssw0rd123',
                'new_password' => 'NewP@ssw0rd123',
                'new_password_confirmation' => 'NewP@ssw0rd123',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('users::messages.password_changed'));

        $this->assertTrue(Hash::check('NewP@ssw0rd123', $user->fresh()->password));
    }

    public function test_change_password_rejects_an_incorrect_current_password(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/change-password', [
                'old_password' => 'wrong-password',
                'new_password' => 'NewP@ssw0rd123',
                'new_password_confirmation' => 'NewP@ssw0rd123',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users::messages.password_incorrect'));
    }

    public function test_change_password_rejects_the_current_password_as_the_new_password(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/change-password', [
                'old_password' => 'P@ssw0rd123',
                'new_password' => 'P@ssw0rd123',
                'new_password_confirmation' => 'P@ssw0rd123',
            ])
            ->assertUnprocessable();
    }

    public function test_forgot_password_sends_an_otp_and_returns_a_reset_token(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.otp', '1234')
            ->assertJsonStructure(['data' => ['token']]);

        $user->refresh();

        $this->assertSame($response->json('data.token'), $user->change_password_token);
        $this->assertNotNull($user->change_password_expired_at);
        $this->assertTrue($user->change_password_expired_at->isFuture());
        $this->assertSame(1, $user->otps()->where('type', 'forgot_password')->count());
    }

    public function test_forgot_password_otp_can_be_verified(): void
    {
        $this->createUser();

        $forgot = $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/verify-forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'otp' => '1234',
            'change_password_token' => $forgot->json('data.token'),
        ])->assertOk();
    }

    public function test_password_can_be_reset_after_otp_verification(): void
    {
        $user = $this->createUser();

        $forgot = $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/verify-forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'otp' => '1234',
            'change_password_token' => $forgot->json('data.token'),
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/reset-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'change_password_token' => $forgot->json('data.token'),
            'password' => 'NewP@ssw0rd123',
            'password_confirmation' => 'NewP@ssw0rd123',
        ])->assertOk();

        $user->refresh();

        $this->assertTrue(Hash::check('NewP@ssw0rd123', $user->password));
        $this->assertNull($user->change_password_token);
        $this->assertNull($user->change_password_expired_at);
    }

    public function test_forgot_password_is_rate_limited_by_security_middleware(): void
    {
        $this->enableRateLimiting(1);
        $this->createUser();

        $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Please slow down.');
    }

    public function test_change_password_rejects_weak_or_mismatched_new_passwords(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/change-password', [
                'old_password' => 'P@ssw0rd123',
                'new_password' => 'weak',
                'new_password_confirmation' => 'weak',
            ])
            ->assertUnprocessable();

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/change-password', [
                'old_password' => 'P@ssw0rd123',
                'new_password' => 'NewP@ssw0rd123',
                'new_password_confirmation' => 'DifferentP@ssw0rd123',
            ])
            ->assertUnprocessable();
    }

    public function test_forgot_password_rejects_an_unknown_phone(): void
    {
        $this->createCountry();

        $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01099998888',
        ])->assertNotFound();
    }

    public function test_verify_forgot_password_rejects_a_wrong_otp(): void
    {
        $this->createUser();

        $forgot = $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/verify-forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'otp' => '9999',
            'change_password_token' => $forgot->json('data.token'),
        ])->assertUnprocessable();
    }

    public function test_verify_forgot_password_rejects_an_invalid_token(): void
    {
        $this->createUser();

        $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/verify-forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'otp' => '1234',
            'change_password_token' => 'invalid-token',
        ])->assertUnprocessable();
    }

    public function test_reset_password_validates_the_new_password_and_token_reuse(): void
    {
        $user = $this->createUser();

        $forgot = $this->postJson('/api/v1/mobile/users/forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
        ])->assertOk();

        $token = $forgot->json('data.token');

        $this->postJson('/api/v1/mobile/users/verify-forget-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'otp' => '1234',
            'change_password_token' => $token,
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/reset-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'change_password_token' => $token,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/mobile/users/reset-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'change_password_token' => $token,
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/mobile/users/reset-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'change_password_token' => $token,
            'password' => 'NewP@ssw0rd123',
            'password_confirmation' => 'DifferentP@ssw0rd123',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/mobile/users/reset-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'change_password_token' => $token,
            'password' => 'NewP@ssw0rd123',
            'password_confirmation' => 'NewP@ssw0rd123',
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/reset-password', [
            'country_code' => '20',
            'phone' => '01011112222',
            'change_password_token' => $token,
            'password' => 'AnotherP@ssw0rd123',
            'password_confirmation' => 'AnotherP@ssw0rd123',
        ])->assertUnprocessable();
    }

    public function test_forgot_password_enforces_otp_resend_cooldown(): void
    {
        $this->createUser();

        $payload = [
            'country_code' => '20',
            'phone' => '01011112222',
        ];

        $this->postJson('/api/v1/mobile/users/forget-password', $payload)->assertOk();
        $this->postJson('/api/v1/mobile/users/forget-password', $payload)->assertUnprocessable();
    }
}
