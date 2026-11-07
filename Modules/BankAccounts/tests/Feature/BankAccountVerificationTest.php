<?php

namespace Modules\BankAccounts\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Tests\Support\BankAccountsApiTestCase;

class BankAccountVerificationTest extends BankAccountsApiTestCase
{
    public function test_correct_otp_verifies_ownership(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $accountId = $this->linkAccount($token, $bank);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts/'.$accountId.'/verify', ['otp' => '1234'])
            ->assertOk()
            ->assertJsonPath('message', __('bankaccounts::messages.account_verified'))
            ->assertJsonPath('data.status', BankAccountStatus::Verified->value)
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.is_primary', true);

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $accountId,
            'status' => BankAccountStatus::Verified->value,
            'is_primary' => true,
        ]);
    }

    public function test_wrong_otp_keeps_the_account_pending(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $accountId = $this->linkAccount($token, $bank);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts/'.$accountId.'/verify', ['otp' => '0000'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('bank_accounts', [
            'id' => $accountId,
            'status' => BankAccountStatus::Pending->value,
            'verified_at' => null,
        ]);
    }

    public function test_resend_otp_respects_the_cooldown_then_becomes_available(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $accountId = $this->linkAccount($token, $bank);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts/'.$accountId.'/resend-otp')
            ->assertUnprocessable();

        $this->expireOtpCooldown($user->phone, $accountId);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts/'.$accountId.'/resend-otp')
            ->assertOk()
            ->assertJsonPath('data.otp', 1234);
    }

    public function test_already_verified_account_cannot_be_verified_again(): void
    {
        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $accountId = $this->linkAccount($token, $bank);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts/'.$accountId.'/verify', ['otp' => '1234'])
            ->assertOk();

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts/'.$accountId.'/verify', ['otp' => '1234'])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('bankaccounts::messages.account_already_verified'));
    }

    public function test_user_cannot_verify_another_users_account(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $bank = $this->createBank();
        $account = $this->createBankAccount($owner, $bank);

        $this->withHeaders($this->bearer($this->tokenFor($other)))
            ->postJson('/api/v1/mobile/bank-accounts/'.$account->id.'/verify', ['otp' => '1234'])
            ->assertNotFound();
    }

    public function test_verification_otp_is_hidden_in_production(): void
    {
        config(['app.env' => 'production']);

        $user = $this->createUser();
        $bank = $this->createBank();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts', [
                'bank_id' => $bank->id,
                'account_holder_name' => $user->name,
                'account_number' => '1010101010101010',
            ])
            ->assertOk()
            ->assertJsonPath('data.otp', null);
    }

    private function linkAccount(string $token, $bank): int
    {
        $response = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/bank-accounts', [
                'bank_id' => $bank->id,
                'account_holder_name' => 'Link Owner',
                'account_number' => '1234123412345678',
            ])
            ->assertOk();

        return (int) $response->json('data.account.id');
    }

    private function expireOtpCooldown(?string $phone, int $accountId): void
    {
        $type = 'bank_account_verification_'.$accountId;
        $key = "otp:cache:{$type}:{$phone}";
        $cached = Cache::get($key);

        $cached['last_sent_at'] = now()->subMinutes(2)->toDateTimeString();
        Cache::put($key, $cached, now()->addMinutes(5));
    }
}
