<?php

namespace Modules\Users\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Users\Tests\Support\UsersApiTestCase;

class ProfileTest extends UsersApiTestCase
{
    public function test_authenticated_user_can_read_their_profile(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.phone', $user->phone)
            ->assertJsonPath('data.country_code', '20');
    }

    public function test_authenticated_user_can_update_profile_details(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'postal_code' => '12345',
                'main_address' => 'Updated address',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.com');

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('12345', $user->postal_code);
        $this->assertSame('Updated address', $user->main_address);
    }

    public function test_authenticated_user_can_upload_replace_and_delete_avatar(): void
    {
        Storage::fake('public');

        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $firstAvatar = UploadedFile::fake()->image('first.jpg', 100, 100);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', ['avatar' => $firstAvatar])
            ->assertOk();

        $user->refresh();
        $firstPath = $user->avatar;

        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $secondAvatar = UploadedFile::fake()->image('second.jpg', 100, 100);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', ['avatar' => $secondAvatar])
            ->assertOk();

        $user->refresh();
        $secondPath = $user->avatar;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', ['delete_avatar' => true])
            ->assertOk();

        $user->refresh();

        $this->assertNull($user->avatar);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_phone_change_updates_phone_and_country_after_verification(): void
    {
        $user = $this->createUser();
        $saudi = $this->createCountry('Saudi Arabia', '966', 9, 'SA');
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', [
                'country_code' => '966',
                'phone' => '501234567',
            ])
            ->assertOk()
            ->assertJsonPath('data.needs_phone_verification', true)
            ->assertJsonPath('data.pending_phone', '966501234567')
            ->assertJsonPath('data.otp_code', 1234);

        $user->refresh();

        $this->assertSame('2001011112222', $user->phone);
        $this->assertNotSame($saudi->id, $user->country_id);

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '966',
            'phone' => '501234567',
            'otp' => '9999',
        ])->assertUnprocessable();

        $this->assertSame('2001011112222', $user->fresh()->phone);

        $this->postJson('/api/v1/mobile/users/verify', [
            'country_code' => '966',
            'phone' => '501234567',
            'otp' => '1234',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('users::messages.phone_updated'));

        $user->refresh();

        $this->assertSame('966501234567', $user->phone);
        $this->assertSame($saudi->id, $user->country_id);
    }

    public function test_authenticated_user_can_delete_their_account(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->deleteJson('/api/v1/mobile/users/profile')
            ->assertOk()
            ->assertJsonPath('message', __('users::messages.account_deleted'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame(0, $user->tokens()->count());

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/users/profile')
            ->assertUnauthorized();
    }

    public function test_profile_requires_a_token(): void
    {
        $this->getJson('/api/v1/mobile/users/profile')
            ->assertUnauthorized();
    }

    public function test_profile_update_rejects_duplicate_email_and_phone(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->createUser([
            'email' => 'other@example.com',
            'phone' => '2001011113333',
        ]);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', [
                'email' => 'other@example.com',
            ])
            ->assertUnprocessable();

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', [
                'country_code' => '20',
                'phone' => '01011113333',
            ])
            ->assertUnprocessable();
    }

    public function test_profile_update_rejects_an_invalid_avatar(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/users/profile', [
                'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable();
    }

    public function test_profile_ignores_a_client_supplied_user_id(): void
    {
        $user = $this->createUser();
        $other = $this->createUser([
            'email' => 'other@example.com',
            'phone' => '2001011113333',
        ]);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/users/profile?user_id='.$other->id.'&id='.$other->id)
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }
}
