<?php

namespace Modules\Users\Tests\Support;

use App\Enums\ActiveStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Models\AdminRoles;
use Modules\Admins\Models\Admins;
use Modules\Settings\Enums\SettingsType;
use Modules\Settings\Models\Settings;
use Modules\Users\Models\User;
use Tests\TestCase;

abstract class UsersApiTestCase extends TestCase
{
    use RefreshDatabase;

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

    protected function enableRateLimiting(int $maxAttempts = 1): void
    {
        config([
            'security.enabled' => true,
            'security.rate_limit.enabled' => true,
            'security.rate_limit.max_attempts' => $maxAttempts,
            'security.rate_limit.decay_minutes' => 1,
            'security.threat_detection.enabled' => false,
            'security.ip_filter.enabled' => false,
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

    protected function createCountry(
        string $name = 'Egypt',
        string $phoneCode = '20',
        int $phoneLength = 11,
        string $code = 'EG'
    ): Settings {
        $country = Settings::firstOrCreate(
            [
                'type' => SettingsType::COUNTRY->value,
                'name_en' => $name,
            ],
            [
                'name_ar' => $name,
                'is_active' => true,
                'data' => [
                    'code' => $code,
                    'phone_code' => $phoneCode,
                    'phone_length' => $phoneLength,
                ],
            ]
        );

        Cache::flush();

        return $country;
    }

    protected function createUser(array $overrides = []): User
    {
        $country = $this->createCountry();

        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '2001011112222',
            'country_id' => $country->id,
            'password' => 'P@ssw0rd123',
            'is_active' => ActiveStatus::Active,
            'phone_verified_at' => now(),
        ], $overrides));
    }

    protected function registrationPayload(array $overrides = []): array
    {
        $country = $this->createCountry();

        return array_merge([
            'name' => 'New User',
            'country_code' => '20',
            'country_id' => $country->id,
            'phone' => '01011112222',
            'email' => 'new-user@example.com',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
        ], $overrides);
    }

    protected function loginPayload(
        string $phone = '01011112222',
        string $countryCode = '20',
        string $password = 'P@ssw0rd123'
    ): array {
        return [
            'country_code' => $countryCode,
            'phone' => $phone,
            'password' => $password,
        ];
    }

    protected function tokenFor(User $user, array $abilities = ['users']): string
    {
        return $user->createToken('users-test', $abilities)->plainTextToken;
    }

    protected function createAdmin(?int $roleId = null, array $overrides = []): Admins
    {
        if ($roleId !== null) {
            DB::table('roles')->updateOrInsert(
                ['id' => $roleId],
                [
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $admin = Admins::create(array_merge([
            'name' => 'Test Admin',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'password' => 'P@ssw0rd123',
            'is_active' => true,
        ], $overrides));

        if ($roleId !== null) {
            AdminRoles::create([
                'admin_id' => $admin->id,
                'role_id' => $roleId,
            ]);
        }

        return $admin;
    }

    protected function createSuperAdmin(array $overrides = []): Admins
    {
        return $this->createAdmin(
            (int) config('admins.base_roles.super_role.id'),
            $overrides
        );
    }

    protected function createRegularAdmin(array $overrides = []): Admins
    {
        return $this->createAdmin(
            (int) config('admins.base_roles.default_role.id'),
            $overrides
        );
    }

    protected function tokenForAdmin(Admins $admin, array $abilities = ['admins']): string
    {
        return $admin->createToken('admins-test', $abilities)->plainTextToken;
    }

    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
