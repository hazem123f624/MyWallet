<?php

namespace Tests\Feature;

use App\Services\Security\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Models\AdminRoles;
use Modules\Admins\Models\Admins;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ApiClient::create([
            'name' => config('security.test_key'),
            'api_key' => 'dashboard-test-key',
            'secret' => str_repeat('a', 64),
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/dashboard/login');
    }

    public function test_login_page_exposes_api_configuration_and_recovery_flow(): void
    {
        $this->get('/dashboard/login')
            ->assertOk()
            ->assertSee('dashboard-api-base', false)
            ->assertSee('dashboard-api-key', false)
            ->assertSee('recoveryPanel', false)
            ->assertSee('Forgot password?', false);
    }

    public function test_admin_can_log_in_and_open_the_dashboard(): void
    {
        $admin = $this->createAdmin();

        $this->post('/dashboard/login', [
            'email' => $admin->email,
            'password' => 'P@ssw0rd123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin, 'admins');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('data-page="overview"', false)
            ->assertSee('dashboard-api-token', false)
            ->assertSee('overviewStatusChart', false)
            ->assertSee('No status data available', false);
    }

    public function test_invalid_admin_credentials_are_rejected(): void
    {
        $this->createAdmin();

        $this->post('/dashboard/login', [
            'email' => 'admin@base.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admins');
    }

    public function test_inactive_admin_cannot_log_in(): void
    {
        $this->createAdmin(['is_active' => false]);

        $this->post('/dashboard/login', [
            'email' => 'admin@base.com',
            'password' => 'P@ssw0rd123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admins');
    }

    public function test_non_super_admin_sees_read_only_status_controls(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admins');

        $this->get('/dashboard/users')
            ->assertOk()
            ->assertSee('data-can-manage-status="0"', false)
            ->assertSee('only a Super Admin can suspend or activate users', false);
    }

    public function test_non_super_admin_cannot_open_admin_management(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admins');

        $this->get('/dashboard/admins')->assertForbidden();
        $this->get('/dashboard/roles')->assertForbidden();
        $this->get('/dashboard/models')->assertForbidden();
    }

    public function test_every_admin_can_open_activity_logs(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admins');

        $this->get('/dashboard/activity-logs')
            ->assertOk()
            ->assertSee('data-page="activity-logs"', false);

        $this->get('/dashboard/transfers')
            ->assertOk()
            ->assertSee('data-page="transfers"', false);

        $this->get('/dashboard/beneficiaries')
            ->assertOk()
            ->assertSee('data-page="beneficiaries"', false);
    }

    public function test_super_admin_can_open_administration_pages(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin, 'admins');

        $this->get('/dashboard/admins')
            ->assertOk()
            ->assertSee('data-page="admins"', false);

        $this->get('/dashboard/roles')
            ->assertOk()
            ->assertSee('data-page="roles"', false);

        $this->get('/dashboard/models')
            ->assertOk()
            ->assertSee('data-page="models"', false);
    }

    public function test_admin_can_log_out(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admins');

        $this->post('/dashboard/logout')->assertRedirect('/dashboard/login');

        $this->assertGuest('admins');
    }

    private function createAdmin(array $overrides = []): Admins
    {
        return Admins::create(array_merge([
            'name' => 'Dashboard Admin',
            'email' => 'admin@base.com',
            'phone' => '01000000000',
            'password' => 'P@ssw0rd123',
            'is_active' => true,
        ], $overrides));
    }

    private function createSuperAdmin(array $overrides = []): Admins
    {
        $admin = $this->createAdmin($overrides);
        $roleId = (int) config('admins.base_roles.super_role.id');

        DB::table('roles')->updateOrInsert(
            ['id' => $roleId],
            ['is_active' => true, 'deleted_at' => null, 'created_at' => now(), 'updated_at' => now()]
        );
        AdminRoles::create(['admin_id' => $admin->id, 'role_id' => $roleId]);

        return $admin->fresh();
    }
}
