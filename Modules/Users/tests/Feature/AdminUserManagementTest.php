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
