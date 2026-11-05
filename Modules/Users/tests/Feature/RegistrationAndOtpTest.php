<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Users\Models\User;
use Modules\Users\Tests\Support\UsersApiTestCase;

class RegistrationAndOtpTest extends UsersApiTestCase
{
    public function test_registration_sends_otp_without_creating_the_user(): void
    {
        $response = $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload());

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.otp', 1234);

        $this->assertDatabaseMissing('users', ['phone' => '+201011112222']);
        $this->assertTrue(Cache::has('otp:cache:registration:+201011112222'));
    }

    public function test_registration_rejects_an_already_registered_phone(): void
    {
        $this->createUser();

        $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload())
            ->assertUnprocessable();
    }

    public function test_registration_verification_creates_user_and_issues_token(): void
    {
        $payload = $this->registrationPayload();

        $this->postJson('/api/v1/mobile/users/register', $payload)->assertOk();

        $response = $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => $payload['phone'],
            'otp' => '1234',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'phone', 'token']]);

        $user = User::where('phone', '+201011112222')->firstOrFail();

        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_registration_verification_rejects_an_invalid_otp(): void
    {
        $payload = $this->registrationPayload();

        $this->postJson('/api/v1/mobile/users/register', $payload)->assertOk();

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => $payload['phone'],
            'otp' => '9999',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['phone' => '+201011112222']);
    }

    public function test_registration_otp_expires(): void
    {
        $payload = $this->registrationPayload();

        $this->postJson('/api/v1/mobile/users/register', $payload)->assertOk();
        $this->travel(6)->minutes();

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => $payload['phone'],
            'otp' => '1234',
        ])->assertUnprocessable();
    }

    public function test_registration_otp_locks_after_maximum_failed_attempts(): void
    {
        $payload = $this->registrationPayload();

        $this->postJson('/api/v1/mobile/users/register', $payload)->assertOk();

        foreach (['1111', '2222', '3333'] as $otp) {
            $this->postJson('/api/v1/mobile/users/verify', [
                'country_code' => '20',
                'phone' => $payload['phone'],
                'otp' => $otp,
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => $payload['phone'],
            'otp' => '1234',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Too many failed attempts. Please request a new OTP.');

        $cooldown = $this->postJson('/api/v1/mobile/users/resend-otp', [
            'country_code' => '20',
            'phone' => $payload['phone'],
        ]);

        $cooldown->assertUnprocessable();
        $this->assertMatchesRegularExpression(
            '/^OTP already sent\. Please wait \d+ seconds? before requesting again\.$/',
            $cooldown->json('message')
        );

        $this->travel(61)->seconds();

        $this->postJson('/api/v1/mobile/users/resend-otp', [
            'country_code' => '20',
            'phone' => $payload['phone'],
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => $payload['phone'],
            'otp' => '1234',
        ])->assertOk();
    }

    public function test_registration_otp_resend_enforces_cooldown(): void
    {
        $payload = $this->registrationPayload();

        $this->postJson('/api/v1/mobile/users/register', $payload)->assertOk();

        $registerCooldown = $this->postJson('/api/v1/mobile/users/register', $payload);
        $registerCooldown->assertUnprocessable();
        $this->assertMatchesRegularExpression(
            '/^OTP already sent\. Please wait \d+ seconds? before requesting again\.$/',
            $registerCooldown->json('message')
        );

        $resendCooldown = $this->postJson('/api/v1/mobile/users/resend-otp', [
            'country_code' => '20',
            'phone' => $payload['phone'],
        ]);
        $resendCooldown->assertUnprocessable();
        $this->assertMatchesRegularExpression(
            '/^OTP already sent\. Please wait \d+ seconds? before requesting again\.$/',
            $resendCooldown->json('message')
        );

        $this->travel(61)->seconds();

        $this->postJson('/api/v1/mobile/users/resend-otp', [
            'country_code' => '20',
            'phone' => $payload['phone'],
        ])->assertOk();
    }

    public function test_registration_is_rate_limited_by_security_middleware(): void
    {
        $this->enableRateLimiting(1);
        $payload = $this->registrationPayload();

        $this->postJson('/api/v1/mobile/users/register', $payload)->assertOk();

        $this->postJson('/api/v1/mobile/users/register', $payload)
            ->assertStatus(429)
            ->assertHeader('Retry-After', 60)
            ->assertJsonPath('message', 'Too many requests. Please slow down.');
    }

    public function test_registration_rejects_an_already_registered_email(): void
    {
        $this->createUser();

        $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload([
            'email' => 'test@example.com',
        ]))->assertUnprocessable();
    }

    public function test_registration_rejects_an_invalid_country_code(): void
    {
        $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload([
            'country_code' => '999',
        ]))->assertUnprocessable();
    }

    public function test_registration_rejects_invalid_phone_formats(): void
    {
        foreach (['12345', '01011abc22'] as $phone) {
            $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload([
                'phone' => $phone,
            ]))->assertUnprocessable();
        }
    }

    public function test_registration_rejects_weak_or_mismatched_passwords(): void
    {
        $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload([
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]))->assertUnprocessable();

        $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload([
            'password_confirmation' => 'DifferentP@ssw0rd123',
        ]))->assertUnprocessable();
    }

    public function test_registration_requires_the_required_fields(): void
    {
        $this->postJson('/api/v1/mobile/users/register', [])->assertUnprocessable();
    }

    public function test_registration_verification_rejects_reused_and_unknown_otps(): void
    {
        $payload = $this->registrationPayload();
        $this->postJson('/api/v1/mobile/users/register', $payload)->assertOk();

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => $payload['phone'],
            'otp' => '1234',
        ])->assertOk();

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => $payload['phone'],
            'otp' => '1234',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '20',
            'phone' => '01099998888',
            'otp' => '1234',
        ])->assertUnprocessable();
    }

    public function test_registration_hides_the_otp_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->postJson('/api/v1/mobile/users/register', $this->registrationPayload())
            ->assertOk()
            ->assertJsonPath('data.otp', null);
    }
}
