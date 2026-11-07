<?php

namespace Modules\BankAccounts\Tests\Feature;

use Modules\BankAccounts\Tests\Support\BankAccountsApiTestCase;

class BankListTest extends BankAccountsApiTestCase
{
    public function test_only_supported_active_banks_are_listed(): void
    {
        $user = $this->createUser();
        $token = $this->tokenFor($user);

        $supported = $this->createBank(['name_en' => 'Supported Bank']);
        $this->createBank(['name_en' => 'Unsupported Bank', 'supports_linking' => false]);
        $this->createBank(['name_en' => 'Inactive Bank', 'is_active' => false]);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/banks')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $supported->id)
            ->assertJsonPath('data.0.supports_linking', true);
    }

    public function test_bank_list_requires_authentication(): void
    {
        $this->getJson('/api/v1/mobile/banks')->assertUnauthorized();
    }

    public function test_suspended_user_cannot_view_supported_banks(): void
    {
        $user = $this->createUser(['is_active' => false]);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/banks')
            ->assertForbidden()
            ->assertJsonPath('message', __('users::messages.account_suspended'));
    }
}
