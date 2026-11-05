<?php

namespace Modules\Users\Tests\Feature;

use Modules\Users\Tests\Support\UsersApiTestCase;

class AccountSuspensionTest extends UsersApiTestCase
{
    public function test_suspending_a_user_revokes_all_of_their_tokens(): void
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

        $this->withHeaders($this->bearer($userToken))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertUnauthorized();
    }
}
