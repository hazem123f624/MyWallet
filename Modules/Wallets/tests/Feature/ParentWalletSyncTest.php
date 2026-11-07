<?php

namespace Modules\Wallets\Tests\Feature;

use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Events\BankAccountPrimaryChanged;
use Modules\BankAccounts\Events\BankAccountUnlinked;
use Modules\BankAccounts\Events\BankAccountVerified;
use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Models\ParentWallet;
use Modules\Wallets\Tests\Support\WalletsApiTestCase;

class ParentWalletSyncTest extends WalletsApiTestCase
{
    public function test_bank_account_verification_links_the_wallet(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        event(new BankAccountVerified($user->id, $account->id));

        $wallet = ParentWallet::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($account->id, $wallet->bank_account_id);
        $this->assertSame(ParentWalletStatus::Active, $wallet->status);
        $this->assertNotNull($wallet->linked_at);
    }

    public function test_primary_account_change_updates_the_wallet(): void
    {
        $user = $this->createUser();
        $firstBank = $this->createBank();
        $secondBank = $this->createBank();
        $first = $this->createBankAccount($user, $firstBank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);
        $second = $this->createBankAccount($user, $secondBank, [
            'is_primary' => false,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->createWallet($user, [
            'bank_account_id' => $first->id,
            'status' => ParentWalletStatus::Active,
            'linked_at' => now(),
        ]);

        $first->update(['is_primary' => false]);
        $second->update(['is_primary' => true]);

        event(new BankAccountPrimaryChanged($user->id, $second->id));

        $wallet = ParentWallet::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($second->id, $wallet->bank_account_id);
        $this->assertSame(ParentWalletStatus::Active, $wallet->status);
    }

    public function test_unlinking_the_funding_account_clears_the_wallet(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->createWallet($user, [
            'bank_account_id' => $account->id,
            'status' => ParentWalletStatus::Active,
            'linked_at' => now(),
        ]);

        $account->delete();

        event(new BankAccountUnlinked($user->id, $account->id));

        $wallet = ParentWallet::where('user_id', $user->id)->firstOrFail();

        $this->assertNull($wallet->bank_account_id);
        $this->assertSame(ParentWalletStatus::Unlinked, $wallet->status);
        $this->assertNull($wallet->linked_at);
    }

    public function test_unlinking_the_primary_account_links_the_promoted_account(): void
    {
        $user = $this->createUser();
        $firstBank = $this->createBank();
        $secondBank = $this->createBank();
        $first = $this->createBankAccount($user, $firstBank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);
        $second = $this->createBankAccount($user, $secondBank, [
            'is_primary' => false,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->createWallet($user, [
            'bank_account_id' => $first->id,
            'status' => ParentWalletStatus::Active,
            'linked_at' => now(),
        ]);

        $first->delete();
        $second->update(['is_primary' => true]);

        event(new BankAccountUnlinked($user->id, $first->id));

        $wallet = ParentWallet::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($second->id, $wallet->bank_account_id);
        $this->assertSame(ParentWalletStatus::Active, $wallet->status);
    }

    public function test_view_synchronizes_a_missed_funding_source_change(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($user, $bank, [
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->createWallet($user);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->getJson('/api/v1/mobile/parent-wallet')
            ->assertOk()
            ->assertJsonPath('data.status', ParentWalletStatus::Active->value)
            ->assertJsonPath('data.funding_source.id', $account->id);
    }
}
