<?php

namespace Modules\Wallets\Tests\Feature;

use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Tests\Support\WalletsApiTestCase;

class ParentWalletFundingSourceTest extends WalletsApiTestCase
{
    public function test_verified_primary_bank_account_can_fund_the_wallet(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->putJson('/api/v1/mobile/parent-wallet/funding-source', [
                'bank_account_id' => $account->id,
            ])
            ->assertOk()
            ->assertJsonPath('message', __('wallets::messages.funding_source_linked'))
            ->assertJsonPath('data.status', ParentWalletStatus::Active->value)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.funding_source.id', $account->id)
            ->assertJsonPath('data.funding_source.account_number', '****'.substr($account->account_number, -4))
            ->assertJsonPath('data.funding_source.is_primary', true);

        $this->assertDatabaseHas('parent_wallets', [
            'user_id' => $user->id,
            'bank_account_id' => $account->id,
            'status' => ParentWalletStatus::Active->value,
        ]);
    }

    public function test_pending_bank_account_cannot_fund_the_wallet(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Pending,
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->putJson('/api/v1/mobile/parent-wallet/funding-source', [
                'bank_account_id' => $account->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.bank_account_not_verified'));
    }

    public function test_non_primary_bank_account_cannot_fund_the_wallet(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($user, $bank, [
            'is_primary' => false,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->putJson('/api/v1/mobile/parent-wallet/funding-source', [
                'bank_account_id' => $account->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.bank_account_not_primary'));
    }

    public function test_another_users_bank_account_cannot_fund_the_wallet(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($owner, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($other)))
            ->putJson('/api/v1/mobile/parent-wallet/funding-source', [
                'bank_account_id' => $account->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.bank_account_not_found'));
    }

    public function test_linking_the_same_account_is_idempotent(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->putJson('/api/v1/mobile/parent-wallet/funding-source', ['bank_account_id' => $account->id])
            ->assertOk();

        $this->withHeaders($this->bearer($token))
            ->putJson('/api/v1/mobile/parent-wallet/funding-source', ['bank_account_id' => $account->id])
            ->assertOk()
            ->assertJsonPath('data.funding_source.id', $account->id);
    }

    public function test_unknown_bank_account_is_rejected(): void
    {
        $user = $this->createUser();

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->putJson('/api/v1/mobile/parent-wallet/funding-source', [
                'bank_account_id' => 999999,
            ])
            ->assertUnprocessable();
    }
}
