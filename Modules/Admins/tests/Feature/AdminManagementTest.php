<?php

namespace Modules\Admins\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Admins\Models\Admins;
use Modules\Admins\Models\Roles;
use Modules\Admins\Tests\Support\AdminsApiTestCase;

class AdminManagementTest extends AdminsApiTestCase
{
    public function test_super_admin_can_manage_administrator_records(): void
    {
        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/admins')
            ->assertOk()
            ->assertJsonPath('success', true);

        $create = $this->withHeaders($this->bearer())
            ->postJson('/api/v1/dashboard/admins', [
                'name' => 'Managed Admin',
                'email' => 'managed@example.com',
                'phone' => '+201099999991',
                'password' => 'P@ssw0rd123',
                'password_confirmation' => 'P@ssw0rd123',
                'job_title' => 'Operations',
                'is_active' => true,
                'roles' => [['role_id' => $this->roleId()]],
            ])
            ->assertOk();

        $adminId = (int) $create->json('data.id');

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/admins/'.$adminId)
            ->assertOk()
            ->assertJsonPath('data.email', 'managed@example.com');

        $this->withHeaders($this->bearer())
            ->putJson('/api/v1/dashboard/admins/'.$adminId, [
                'name' => 'Managed Admin Updated',
                'job_title' => 'Supervisor',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Managed Admin Updated');

        $this->withHeaders($this->bearer())
            ->patchJson('/api/v1/dashboard/admins/'.$adminId.'/toggle-active')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/dashboard/admins/'.$adminId.'/roles/'.$this->roleId())
            ->assertOk();

        $this->assertDatabaseMissing('admin_roles', [
            'admin_id' => $adminId,
            'role_id' => $this->roleId(),
        ]);

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/dashboard/admins/'.$adminId)
            ->assertOk();

        $this->assertSoftDeleted('admins', ['id' => $adminId]);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/admins/trash')
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->patchJson('/api/v1/dashboard/admins/'.$adminId.'/restore')
            ->assertOk();

        $this->assertDatabaseHas('admins', ['id' => $adminId, 'deleted_at' => null]);

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/dashboard/admins/'.$adminId.'/force-delete')
            ->assertOk();

        $this->assertDatabaseMissing('admins', ['id' => $adminId]);
    }

    public function test_super_admin_can_manage_roles_permissions_and_members(): void
    {
        $permission = DB::table('model_permissions')->first();
        $this->assertNotNull($permission);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/roles')
            ->assertOk()
            ->assertJsonPath('success', true);

        $create = $this->withHeaders($this->bearer())
            ->postJson('/api/v1/dashboard/roles', [
                'name' => ['ar' => 'دور اختبار', 'en' => 'Test Role'],
                'description' => ['ar' => 'وصف اختبار', 'en' => 'Test description'],
                'permissions' => [[
                    'model_id' => $permission->model_id,
                    'permission_id' => $permission->permission_id,
                ]],
            ])
            ->assertOk();

        $roleId = (int) $create->json('data.id');

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/roles/'.$roleId)
            ->assertOk()
            ->assertJsonPath('data.id', $roleId);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/roles/'.$roleId.'/permissions')
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->putJson('/api/v1/dashboard/roles/'.$roleId, [
                'name' => ['ar' => 'دور محدث', 'en' => 'Updated Role'],
                'description' => ['ar' => 'وصف محدث', 'en' => 'Updated description'],
                'permissions' => [[
                    'model_id' => $permission->model_id,
                    'permission_id' => $permission->permission_id,
                ]],
            ])
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->patchJson('/api/v1/dashboard/roles/'.$roleId.'/toggle-active')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/roles/'.$roleId.'/available-admins')
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->postJson('/api/v1/dashboard/roles/'.$roleId.'/admins', [
                'admin_ids' => [$this->admin->id],
            ])
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/roles/'.$roleId.'/admins')
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/dashboard/admins/'.$this->admin->id.'/roles/'.$roleId)
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/dashboard/roles/'.$roleId)
            ->assertOk();

        $this->assertSoftDeleted('roles', ['id' => $roleId]);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/roles/trash')
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->patchJson('/api/v1/dashboard/roles/'.$roleId.'/restore')
            ->assertOk();

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/dashboard/roles/'.$roleId.'/force-delete')
            ->assertOk();

        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    public function test_dashboard_analytics_are_available(): void
    {
        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/analytics/overview?days=30')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'range_days',
                    'users' => ['total', 'active', 'suspended', 'deleted', 'new', 'series' => ['labels', 'values']],
                    'admins' => ['total'],
                    'roles' => ['total'],
                    'activity' => ['total', 'series' => ['labels', 'values'], 'top_actions'],
                    'generated_at',
                ],
            ]);
    }

    public function test_permission_models_and_activity_logs_are_available(): void
    {
        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/models')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/models?search=Users')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/activity-logs')
            ->assertOk()
            ->assertJsonPath('success', true);

        $role = Roles::create(['is_active' => true]);

        $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/activity-logs/'.$role->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $export = $this->withHeaders($this->bearer())
            ->getJson('/api/v1/dashboard/activity-logs/'.$role->id.'/export')
            ->assertOk();

        $fileName = $export->json('data.file_name');
        $this->assertNotNull($fileName);

        $this->withHeaders($this->bearer())
            ->get('/api/v1/dashboard/activity-logs/export/'.$fileName.'/download')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_password_recovery_flow_uses_the_api_endpoints(): void
    {
        $admin = $this->createAdmin([
            'email' => 'recovery@example.com',
            'phone' => '+201099999992',
        ]);

        $forgot = $this->postJson('/api/v1/forget-password', [
            'email' => $admin->email,
        ])->assertOk();

        $token = $forgot->json('data.change_password_token');
        $this->assertNotEmpty($token);

        $this->postJson('/api/v1/resend-forgot-password-otp', [
            'email' => $admin->email,
        ])->assertOk();

        $this->postJson('/api/v1/verify-forget-password-otp', [
            'email' => $admin->email,
            'otp' => '1234',
            'change_password_token' => $token,
        ])->assertOk();

        $this->postJson('/api/v1/reset-password', [
            'email' => $admin->email,
            'password' => 'NewP@ssw0rd123',
            'new_password_confirmation' => 'NewP@ssw0rd123',
            'change_password_token' => $token,
        ])->assertOk();

        $this->assertTrue(Hash::check('NewP@ssw0rd123', $admin->fresh()->password));
        $this->assertTrue($admin->fresh() instanceof Admins);
    }
}
