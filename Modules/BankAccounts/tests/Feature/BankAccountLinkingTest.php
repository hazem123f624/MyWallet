<?php

namespace Modules\BankAccounts\Tests\Feature;

use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Models\BankAccount;
use Modules\BankAccounts\Tests\Support\BankAccountsApiTestCase;

class BankAccountLinkingTest extends BankAccountsApiTestCase
{
    public function test_user_can_link_their_first_bank_account(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $response = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts', [
                'bank_id' => $bank->id,
                'account_holder_name' => $user->name,
                'account_number' => '1234 5678 9012 3456',
                'iban' => 'eg380019000500000000263180002',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('bankaccounts::messages.account_linked'))
            ->assertJsonPath('data.otp', 1234)
            ->assertJsonPath('data.account.status', BankAccountStatus::Pending->value)
            ->assertJsonPath('data.account.is_primary', true)
            ->assertJsonPath('data.account.is_verified', false)
            ->assertJsonPath('data.account.account_number', '****3456')
            ->assertJsonMissingPath('data.account.account_number_hash');

        $this->assertStringNotContainsString('1234567890123456', $response->getContent());

        $this->assertDatabaseHas('bank_accounts', [
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'account_number_hash' => hash('sha256', '1234567890123456'),
            'account_number_last4' => '3456',
            'is_primary' => true,
            'status' => BankAccountStatus::Pending->value,
        ]);
    }

    public function test_only_the_first_linked_account_is_primary(): void
    {
        $user = $this->createUser();
        $firstBank = $this->createBank();
        $secondBank = $this->createBank();
        $token = $this->tokenFor($user);

        $this->createBankAccount($user, $firstBank, ['is_primary' => true]);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts', [
                'bank_id' => $secondBank->id,
                'account_holder_name' => $user->name,
                'account_number' => '5555666677778888',
            ])
            ->assertOk()
            ->assertJsonPath('data.account.is_primary', false);
    }

    public function test_duplicate_bank_account_is_rejected(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $this->createBankAccount($user, $bank);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts', [
                'bank_id' => $bank->id,
                'account_holder_name' => $user->name,
                'account_number' => $this->existingAccountNumber($user, $bank),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('bankaccounts::messages.account_already_linked'));
    }

    public function test_unsupported_bank_cannot_be_linked(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank(['supports_linking' => false]);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts', [
                'bank_id' => $bank->id,
                'account_holder_name' => $user->name,
                'account_number' => '9999888877776666',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bank_id');
    }

    public function test_invalid_account_number_is_rejected(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts', [
                'bank_id' => $bank->id,
                'account_holder_name' => $user->name,
                'account_number' => '12ab',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account_number');
    }

    public function test_user_cannot_see_another_users_bank_accounts(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($owner, $bank);

        $this->withHeaders($this->bearer($this->tokenFor($other)))
            ->getJson('/api/v1/mobile/bank-accounts')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($this->bearer($this->tokenFor($other)))
            ->deleteJson('/api/v1/mobile/bank-accounts/'.$account->id)
            ->assertNotFound();
    }

    public function test_linking_requires_authentication(): void
    {
        $bank = $this->createBank();

        $this->postJson('/api/v1/mobile/bank-accounts', [
            'bank_id' => $bank->id,
            'account_holder_name' => 'User',
            'account_number' => '1111222233334444',
        ])->assertUnauthorized();
    }

    private function existingAccountNumber($user, $bank): string
    {
        $account = BankAccount::query()
            ->where('user_id', $user->id)
            ->where('bank_id', $bank->id)
            ->firstOrFail();

        return $account->account_number;
    }
}
