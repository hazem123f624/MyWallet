<?php

namespace Modules\Users\Tests\Feature;

use Modules\Users\Tests\Support\UsersApiTestCase;

class SecurityTest extends UsersApiTestCase
{
    public function test_request_without_the_api_key_is_rejected(): void
    {
        config([
            'security.enabled' => true,
            'security.api_key.enabled' => true,
        ]);

        $this->createCountry();

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload())
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Missing required header: X-API-Token');
    }

    public function test_oversized_payload_is_rejected(): void
    {
        config([
            'security.enabled' => true,
            'security.payload_size.enabled' => true,
            'security.payload_size.max_kb' => 1,
        ]);

        $this->createCountry();

        $this->postJson('/api/v1/mobile/users/login', [
            'country_code' => '20',
            'phone' => '01011112222',
            'password' => str_repeat('a', 2000),
        ])
            ->assertStatus(413)
            ->assertJsonPath('message', 'Payload too large. Maximum allowed: 1 KB.');
    }

    public function test_passwords_and_transaction_pins_are_never_returned(): void
    {
        $user = $this->createUser(['transaction_pin' => '2468']);
        $token = $this->tokenFor($user);

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload())
            ->assertOk()
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.transaction_pin');

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertOk()
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.transaction_pin');
    }
}
