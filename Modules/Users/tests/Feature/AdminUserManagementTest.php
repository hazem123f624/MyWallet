<?php

namespace Modules\Users\Tests\Feature;

use Modules\Users\Models\User;
use Modules\Users\Tests\Support\UsersApiTestCase;

class AdminUserManagementTest extends UsersApiTestCase
{
    public function test_super_admin_can_list_and_view_users(): void
    {
        $admin = $this->createSuperAdmin();
        $adminToken = $this->tokenForAdmin($admin);
        $user = $this->createUser();

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id]);

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_super_admin_can_suspend_and_activate_a_user(): void
    {
        $admin = $this->createSuperAdmin();
        $adminToken = $this->tokenForAdmin($admin);
        $user = $this->createUser();
        $userToken = $this->tokenFor($user);

        $this->withHeaders($this->bearer($adminToken))
            ->patchJson('/api/v1/dashboard/users/'.$user->id.'/toggle-active')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        $this->withHeaders($this->bearer($adminToken))
            ->patchJson('/api/v1/dashboard/users/'.$user->id.'/toggle-active')
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_super_admin_can_fetch_user_statistics(): void
    {
        $admin = $this->createSuperAdmin();
        $adminToken = $this->tokenForAdmin($admin);
        $this->createUser([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'phone' => '+201011116666',
        ]);
        $this->createUser([
            'name' => 'Suspended User',
            'email' => 'suspended@example.com',
            'phone' => '+201011117777',
            'is_active' => false,
        ]);
        $deleted = $this->createUser([
            'name' => 'Deleted User',
            'email' => 'deleted@example.com',
            'phone' => '+201011118888',
        ]);
        $deleted->delete();

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/users/statistics')
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.active', 1)
            ->assertJsonPath('data.suspended', 1)
            ->assertJsonPath('data.trashed', 1);
    }

    public function test_super_admin_can_search_and_sort_users(): void
    {
        $admin = $this->createSuperAdmin();
        $adminToken = $this->tokenForAdmin($admin);
        $alice = $this->createUser([
            'name' => 'Alice Search',
            'email' => 'alice@example.com',
            'phone' => '+201011114444',
        ]);
        $this->createUser([
            'name' => 'Bob Search',
            'email' => 'bob@example.com',
            'phone' => '+201011115555',
        ]);

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/users?search=alice&sort_by=name&sort_dir=asc')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $alice->id);

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/users?search=01011114444')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $alice->id);
    }

    public function test_super_admin_can_soft_delete_restore_and_force_delete_a_user(): void
    {
        $admin = $this->createSuperAdmin();
        $adminToken = $this->tokenForAdmin($admin);
        $user = $this->createUser();

        $this->withHeaders($this->bearer($adminToken))
            ->deleteJson('/api/v1/dashboard/users/'.$user->id)
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload())
            ->assertUnprocessable();

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/users/trash')
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id]);

        $this->withHeaders($this->bearer($adminToken))
            ->patchJson('/api/v1/dashboard/users/'.$user->id.'/restore')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);

        $this->postJson('/api/v1/mobile/users/login', $this->loginPayload())
            ->assertOk();

        $this->withHeaders($this->bearer($adminToken))
            ->deleteJson('/api/v1/dashboard/users/'.$user->id)
            ->assertOk();

        $this->withHeaders($this->bearer($adminToken))
            ->deleteJson('/api/v1/dashboard/users/'.$user->id.'/force-delete')
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_non_super_admin_cannot_change_user_status(): void
    {
        $admin = $this->createRegularAdmin();
        $adminToken = $this->tokenForAdmin($admin);
        $user = $this->createUser();
        $userToken = $this->tokenFor($user);

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/users')
            ->assertOk();

        $this->withHeaders($this->bearer($adminToken))
            ->patchJson('/api/v1/dashboard/users/'.$user->id.'/toggle-active')
            ->assertForbidden();

        $user->refresh();

        $this->assertTrue($user->is_active);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_user_and_admin_tokens_are_not_interchangeable(): void
    {
        $user = $this->createUser();
        $userToken = $this->tokenFor($user);

        $this->withHeaders($this->bearer($userToken))
            ->getJson('/api/v1/dashboard/users')
            ->assertForbidden();

        $admin = $this->createSuperAdmin();
        $adminToken = $this->tokenForAdmin($admin);

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertForbidden();
    }
}
