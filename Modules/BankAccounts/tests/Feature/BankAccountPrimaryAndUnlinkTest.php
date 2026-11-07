<?php

namespace Modules\BankAccounts\Tests\Feature;

use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Models\BankAccount;
use Modules\BankAccounts\Tests\Support\BankAccountsApiTestCase;

class BankAccountPrimaryAndUnlinkTest extends BankAccountsApiTestCase
{
    public function test_verified_account_can_become_the_only_primary_account(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $otherBank = $this->createBank();
        $token = $this->tokenFor($user);

        $primary = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);
        $candidate = $this->createBankAccount($user, $otherBank, [
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withHeaders($this->bearer($token))
            ->patchJson('/api/v1/mobile/bank-accounts/'.$candidate->id.'/primary')
            ->assertOk()
            ->assertJsonPath('data.id', $candidate->id)
            ->assertJsonPath('data.is_primary', true);

        $this->assertFalse($primary->fresh()->is_primary);
        $this->assertTrue($candidate->fresh()->is_primary);
        $this->assertSame(1, BankAccount::where('user_id', $user->id)->where('is_primary', true)->count());
    }

    public function test_pending_account_cannot_become_primary(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);
        $account = $this->createBankAccount($user, $bank);

        $this->withHeaders($this->bearer($token))
            ->patchJson('/api/v1/mobile/bank-accounts/'.$account->id.'/primary')
            ->assertUnprocessable()
            ->assertJsonPath('message', __('bankaccounts::messages.account_not_verified'));

        $this->assertFalse($account->fresh()->is_primary);
    }

    public function test_unlinking_a_non_primary_account_keeps_the_primary(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $otherBank = $this->createBank();
        $token = $this->tokenFor($user);

        $primary = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);
        $secondary = $this->createBankAccount($user, $otherBank);

        $this->withHeaders($this->bearer($token))
            ->deleteJson('/api/v1/mobile/bank-accounts/'.$secondary->id)
            ->assertOk()
            ->assertJsonPath('message', __('bankaccounts::messages.account_unlinked'));

        $this->assertDatabaseMissing('bank_accounts', ['id' => $secondary->id]);
        $this->assertTrue($primary->fresh()->is_primary);
    }

    public function test_unlinking_the_primary_promotes_another_account(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $otherBank = $this->createBank();
        $token = $this->tokenFor($user);

        $primary = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);
        $next = $this->createBankAccount($user, $otherBank, [
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withHeaders($this->bearer($token))
            ->deleteJson('/api/v1/mobile/bank-accounts/'.$primary->id)
            ->assertOk();

        $this->assertDatabaseMissing('bank_accounts', ['id' => $primary->id]);
        $this->assertTrue($next->fresh()->is_primary);
    }

    public function test_unlinking_the_only_account_leaves_no_primary(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);
        $primary = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withHeaders($this->bearer($token))
            ->deleteJson('/api/v1/mobile/bank-accounts/'.$primary->id)
            ->assertOk();

        $this->assertSame(0, BankAccount::where('user_id', $user->id)->count());
    }

    public function test_user_cannot_make_another_users_account_primary(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($owner, $bank, [
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($other)))
            ->patchJson('/api/v1/mobile/bank-accounts/'.$account->id.'/primary')
            ->assertNotFound();
    }
}
