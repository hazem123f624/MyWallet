<?php

namespace Modules\Admins\Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Admins\Database\Seeders\ModulePermissionsSeeder;
use Modules\Admins\Database\Seeders\RolesDatabaseSeeder;
use Modules\Admins\Models\AdminRoles;
use Modules\Admins\Models\Admins;
use Tests\TestCase;

abstract class AdminsApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Admins $admin;

    protected string $token;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableSecurityMiddleware();
        Cache::flush();
        config(['otp.cooldown_seconds' => 0]);

        $this->seed(ModulePermissionsSeeder::class);
        $this->seed(RolesDatabaseSeeder::class);

        $this->admin = $this->createAdmin(['email' => 'admin-test@example.com']);
        AdminRoles::create([
            'admin_id' => $this->admin->id,
            'role_id' => (int) config('admins.base_roles.super_role.id'),
        ]);
        $this->admin->refresh();

        $this->token = $this->admin->createToken('admins-test', ['admins'])->plainTextToken;
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

    protected function createAdmin(array $overrides = []): Admins
    {
        $sequence = ++self::$sequence;

        return Admins::create(array_merge([
            'name' => 'Test Admin '.$sequence,
            'email' => 'admin-'.$sequence.'@example.com',
            'phone' => '+2010'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'password' => 'P@ssw0rd123',
            'job_title' => 'Administrator',
            'is_active' => true,
        ], $overrides));
    }

    protected function bearer(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    protected function roleId(): int
    {
        return (int) config('admins.base_roles.default_role.id');
    }
}
