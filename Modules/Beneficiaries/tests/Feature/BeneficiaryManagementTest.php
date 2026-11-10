<?php

namespace Modules\Beneficiaries\Tests\Feature;

use Modules\Admins\Models\Admins;
use Modules\Beneficiaries\Tests\Support\BeneficiariesApiTestCase;

class BeneficiaryManagementTest extends BeneficiariesApiTestCase
{
    public function test_user_can_manage_beneficiaries(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $create = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/beneficiaries', [
                'name' => 'Ahmed Ali',
                'bank_name' => 'National Bank of Egypt',
                'bank_code' => 'NBE',
                'account_number' => '1234 5678 9012 3456',
                'iban' => 'eg380019000500000000263180002',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Ahmed Ali')
            ->assertJsonPath('data.account_number', '****3456');

        $id = (int) $create->json('data.id');

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/beneficiaries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/beneficiaries/'.$id)
            ->assertOk()
            ->assertJsonPath('data.bank_name', 'National Bank of Egypt');

        $this->withHeaders($this->bearer($token))
            ->patchJson('/api/v1/mobile/beneficiaries/'.$id, [
                'name' => 'Ahmed Updated',
                'bank_name' => 'Banque Misr',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Ahmed Updated');

        $this->withHeaders($this->bearer($token))
            ->deleteJson('/api/v1/mobile/beneficiaries/'.$id)
            ->assertOk();

        $this->assertSoftDeleted('beneficiaries', ['id' => $id]);
    }

    public function test_duplicate_beneficiary_is_rejected(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);
        $payload = [
            'name' => 'Ahmed Ali',
            'bank_name' => 'NBE',
            'account_number' => '1234567890123456',
        ];

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/beneficiaries', $payload)
            ->assertOk();

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/beneficiaries', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', __('beneficiaries::messages.already_exists'));
    }

    public function test_user_cannot_manage_another_users_beneficiary(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $ownerToken = $this->tokenFor($owner);

        $id = (int) $this->withHeaders($this->bearer($ownerToken))
            ->postJson('/api/v1/mobile/beneficiaries', [
                'name' => 'Owner Beneficiary',
                'bank_name' => 'NBE',
                'account_number' => '1111222233334444',
            ])
            ->assertOk()
            ->json('data.id');

        $otherToken = $this->tokenFor($other);

        $this->withHeaders($this->bearer($otherToken))
            ->getJson('/api/v1/mobile/beneficiaries/'.$id)
            ->assertNotFound();

        $this->withHeaders($this->bearer($otherToken))
            ->patchJson('/api/v1/mobile/beneficiaries/'.$id, ['name' => 'Stolen'])
            ->assertNotFound();

        $this->withHeaders($this->bearer($otherToken))
            ->deleteJson('/api/v1/mobile/beneficiaries/'.$id)
            ->assertNotFound();
    }

    public function test_admin_can_list_and_view_beneficiaries(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $id = (int) $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/beneficiaries', [
                'name' => 'Admin Visible',
                'bank_name' => 'CIB',
                'account_number' => '5555666677778888',
            ])
            ->assertOk()
            ->json('data.id');

        $admin = Admins::create([
            'name' => 'Admin',
            'email' => 'beneficiary-admin@example.com',
            'phone' => '+201099991111',
            'password' => 'P@ssw0rd123',
            'is_active' => true,
        ]);
        $adminToken = $admin->createToken('admins-test', ['admins'])->plainTextToken;

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/beneficiaries')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/beneficiaries/'.$id)
            ->assertOk()
            ->assertJsonPath('data.id', $id);
    }

    public function test_beneficiary_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/mobile/beneficiaries')->assertUnauthorized();
        $this->postJson('/api/v1/mobile/beneficiaries', [])->assertUnauthorized();
    }
}
