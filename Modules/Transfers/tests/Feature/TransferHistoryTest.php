<?php

namespace Modules\Transfers\Tests\Feature;

use Illuminate\Support\Str;
use Modules\Transfers\Enums\TransferStatus;
use Modules\Transfers\Models\Transfer;
use Modules\Transfers\Tests\Support\TransfersApiTestCase;
use Modules\Users\Models\User;
use Modules\Wallets\Models\WalletOperation;

class TransferHistoryTest extends TransfersApiTestCase
{
    public function test_history_returns_only_the_users_transfers_with_pagination(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->tokenFor($user);

        $first = $this->createTransfer($user, $subWallet, $beneficiary, 100);
        $second = $this->createTransfer($user, $subWallet, $beneficiary, 150);

        [$otherUser, , $otherSubWallet, $otherBeneficiary] = $this->fixture();
        $this->createTransfer($otherUser, $otherSubWallet, $otherBeneficiary, 200);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history?per_page=1')
            ->assertOk()
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $second->id);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history?per_page=1&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $first->id);
    }

    public function test_history_filters_by_status(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->tokenFor($user);

        $this->createTransfer($user, $subWallet, $beneficiary, 100, TransferStatus::Successful);
        $failed = $this->createTransfer($user, $subWallet, $beneficiary, 150, TransferStatus::Failed);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history?status=failed')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $failed->id)
            ->assertJsonPath('data.0.status', TransferStatus::Failed->value);
    }

    public function test_history_searches_by_reference(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->tokenFor($user);

        $this->createTransfer($user, $subWallet, $beneficiary, 100);
        $target = $this->createTransfer($user, $subWallet, $beneficiary, 150);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history?reference='.urlencode(substr($target->reference, -8)))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_history_filters_by_date_range(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->tokenFor($user);

        $transfer = $this->createTransfer($user, $subWallet, $beneficiary, 100);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history?date_from='.now()->toDateString().'&date_to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $transfer->id);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history?date_to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history?date_from='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertJsonPath('pagination.total', 0);
    }

    public function test_history_validates_filters(): void
    {
        [$user] = $this->fixture();
        $token = $this->bearer($this->tokenFor($user));

        $this->withHeaders($token)
            ->getJson('/api/v1/mobile/transfers/history?status=unknown')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->withHeaders($token)
            ->getJson('/api/v1/mobile/transfers/history?date_from=2026-11-10&date_to=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');

        $this->withHeaders($token)
            ->getJson('/api/v1/mobile/transfers/history?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_history_never_includes_internal_wallet_operations(): void
    {
        [$user, $wallet, $subWallet] = $this->fixture();
        $token = $this->tokenFor($user);

        WalletOperation::create([
            'user_id' => $user->id,
            'parent_wallet_id' => $wallet->id,
            'from_sub_wallet_id' => null,
            'to_sub_wallet_id' => $subWallet->id,
            'type' => 'allocated',
            'amount' => 100,
            'currency' => 'EGP',
            'description' => 'Allocated to Spending',
        ]);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/history')
            ->assertOk()
            ->assertJsonPath('pagination.total', 0);
    }

    public function test_history_requires_authentication(): void
    {
        $this->getJson('/api/v1/mobile/transfers/history')->assertUnauthorized();
    }

    private function createTransfer(
        User $user,
        $subWallet,
        $beneficiary,
        float $amount = 100,
        TransferStatus $status = TransferStatus::Successful
    ): Transfer {
        return Transfer::create([
            'user_id' => $user->id,
            'parent_wallet_id' => $subWallet->parent_wallet_id,
            'sub_wallet_id' => $subWallet->id,
            'beneficiary_id' => $beneficiary->id,
            'reference' => 'TRX-'.Str::upper(Str::ulid()),
            'client_reference' => null,
            'sub_wallet_name' => $subWallet->name,
            'beneficiary_name' => $beneficiary->name,
            'beneficiary_bank_name' => $beneficiary->bank_name,
            'beneficiary_account_last4' => $beneficiary->account_number_last4,
            'amount' => $amount,
            'currency' => 'EGP',
            'status' => $status,
            'provider_name' => 'fake',
            'provider_reference' => 'HISTORY-'.uniqid(),
            'requested_at' => now(),
            'processed_at' => now(),
            'completed_at' => now(),
        ]);
    }

    private function fixture(): array
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user);
        $subWallet = $this->createSubWallet($wallet);
        $beneficiary = $this->createBeneficiary($user);

        return [$user, $wallet, $subWallet, $beneficiary];
    }
}
