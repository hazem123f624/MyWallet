<?php

namespace Modules\Wallets\Tests\Feature;

use Modules\Wallets\Models\WalletOperation;
use Modules\Wallets\Tests\Support\WalletsApiTestCase;

class WalletOperationHistoryTest extends WalletsApiTestCase
{
    public function test_user_can_list_private_wallet_operations_with_pagination(): void
    {
        [$user, $wallet, $subWallet] = $this->fixture();
        $token = $this->tokenFor($user);

        $this->createOperation($user->id, $wallet->id, $subWallet->id, 'allocated', 100);
        $this->createOperation($user->id, $wallet->id, $subWallet->id, 'redistributed', 50);
        $this->createOperation($user->id, $wallet->id, $subWallet->id, 'sub_wallet_returned', 25);

        [$otherUser, $otherWallet, $otherSubWallet] = $this->fixture();
        $this->createOperation($otherUser->id, $otherWallet->id, $otherSubWallet->id, 'allocated', 999);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/parent-wallet/operations?per_page=2')
            ->assertOk()
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('data.0.is_private', true)
            ->assertJsonCount(2, 'data');

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/parent-wallet/operations?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_filter_operations_by_type(): void
    {
        [$user, $wallet, $subWallet] = $this->fixture();
        $token = $this->tokenFor($user);

        $this->createOperation($user->id, $wallet->id, $subWallet->id, 'allocated', 100);
        $this->createOperation($user->id, $wallet->id, $subWallet->id, 'redistributed', 50);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/parent-wallet/operations?type=redistributed')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'redistributed');
    }

    public function test_user_can_filter_operations_by_date(): void
    {
        [$user, $wallet, $subWallet] = $this->fixture();
        $token = $this->tokenFor($user);

        $this->createOperation($user->id, $wallet->id, $subWallet->id, 'allocated', 100);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/parent-wallet/operations?date_from='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/parent-wallet/operations?date_to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/parent-wallet/operations?date_from='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertJsonPath('pagination.total', 0);
    }

    public function test_operation_history_validates_filters(): void
    {
        [$user] = $this->fixture();
        $token = $this->bearer($this->tokenFor($user));

        $this->withHeaders($token)
            ->getJson('/api/v1/mobile/parent-wallet/operations?type=unknown')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');

        $this->withHeaders($token)
            ->getJson('/api/v1/mobile/parent-wallet/operations?date_from=2026-11-10&date_to=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');

        $this->withHeaders($token)
            ->getJson('/api/v1/mobile/parent-wallet/operations?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_operation_history_requires_authentication(): void
    {
        $this->getJson('/api/v1/mobile/parent-wallet/operations')->assertUnauthorized();
    }

    private function createOperation(
        int $userId,
        int $walletId,
        int $subWalletId,
        string $type,
        float $amount
    ): WalletOperation {
        return WalletOperation::create([
            'user_id' => $userId,
            'parent_wallet_id' => $walletId,
            'from_sub_wallet_id' => in_array($type, ['redistributed', 'sub_wallet_returned'], true) ? $subWalletId : null,
            'to_sub_wallet_id' => in_array($type, ['allocated', 'redistributed'], true) ? $subWalletId : null,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'EGP',
            'description' => ucfirst(str_replace('_', ' ', $type)),
        ]);
    }

    private function fixture(): array
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user);
        $subWallet = $this->createSubWallet($wallet);

        return [$user, $wallet, $subWallet];
    }
}
