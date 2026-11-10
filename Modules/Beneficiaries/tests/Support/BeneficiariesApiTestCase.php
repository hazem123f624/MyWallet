<?php

namespace Modules\Beneficiaries\Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Users\Models\User;
use Tests\TestCase;

abstract class BeneficiariesApiTestCase extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableSecurityMiddleware();
        Cache::flush();
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function disableSecurityMiddleware(): void
    {
        config([
            'security.enabled' => false,
            'security.threat_detection.enabled' => false,
            'security.ip_filter.enabled' => false,
            'security.rate_limit.enabled' => false,
            'security.force_json.enabled' => false,
            'security.payload_size.enabled' => false,
            'security.security_headers.enabled' => false,
            'security.cors.enabled' => false,
            'security.api_key.enabled' => false,
            'security.timestamp.enabled' => false,
            'security.signature.enabled' => false,
            'security.nonce.enabled' => false,
            'security.logging.enabled' => false,
        ]);
    }

    protected function createUser(array $overrides = []): User
    {
        $sequence = ++self::$sequence;

        return User::create(array_merge([
            'name' => 'Beneficiary User '.$sequence,
            'email' => 'beneficiary-user-'.$sequence.'@example.com',
            'phone' => '+2010'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'password' => 'P@ssw0rd123',
            'is_active' => true,
            'phone_verified_at' => now(),
        ], $overrides));
    }

    protected function tokenFor(User $user, array $abilities = ['users']): string
    {
        return $user->createToken('beneficiaries-test', $abilities)->plainTextToken;
    }

    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
