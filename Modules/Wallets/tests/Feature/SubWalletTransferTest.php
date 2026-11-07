<?php

namespace Modules\Wallets\Tests\Feature;

use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Models\WalletOperation;
use Modules\Wallets\Tests\Support\WalletsApiTestCase;

class SubWalletTransferTest extends WalletsApiTestCase
{
    public function test_allocation_moves_funds_from_parent_to_sub_wallet(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet(20000));
        $subWallet = $this->createSubWallet($wallet, ['name' => 'Rent']);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/sub-wallets/'.$subWallet->id.'/allocate', [
                'amount' => 7000,
            ])
            ->assertOk()
            ->assertJsonPath('message', __('wallets::messages.sub_wallet_allocated'))
            ->assertJsonPath('data.available_balance', '7000.00');

        $this->assertSame('13000.00', $wallet->fresh()->available_balance);
        $this->assertSame('7000.00', $subWallet->fresh()->available_balance);
        $this->assertSame('20000.00', $wallet->fresh()->total_allocated_balance);

        $operation = WalletOperation::where('parent_wallet_id', $wallet->id)->latest()->firstOrFail();

        $this->assertSame('allocated', $operation->type->value);
        $this->assertSame('7000.00', $operation->amount);
        $this->assertNull($operation->from_sub_wallet_id);
        $this->assertSame($subWallet->id, $operation->to_sub_wallet_id);
    }

    public function test_allocation_above_parent_available_balance_is_rejected(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet(100));
        $subWallet = $this->createSubWallet($wallet);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/sub-wallets/'.$subWallet->id.'/allocate', [
                'amount' => 101,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.insufficient_parent_balance'));

        $this->assertSame('100.00', $wallet->fresh()->available_balance);
        $this->assertSame('0.00', $subWallet->fresh()->available_balance);
        $this->assertSame(0, WalletOperation::count());
    }

    public function test_invalid_allocation_amount_is_rejected(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet());
        $subWallet = $this->createSubWallet($wallet);
        $token = $this->tokenFor($user);

        foreach ([0, -5, '1.234'] as $amount) {
            $this->withHeaders($this->bearer($token))
                ->postJson('/api/v1/mobile/sub-wallets/'.$subWallet->id.'/allocate', [
                    'amount' => $amount,
                ])
                ->assertUnprocessable();
        }
    }

    public function test_redistribution_moves_funds_between_sub_wallets(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet(10000));
        $source = $this->createSubWallet($wallet, [
            'name' => 'Food',
            'available_balance' => 5000,
        ]);
        $destination = $this->createSubWallet($wallet, [
            'name' => 'Rent',
            'available_balance' => 1000,
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/sub-wallets/'.$source->id.'/redistribute', [
                'destination_sub_wallet_id' => $destination->id,
                'amount' => 2000,
            ])
            ->assertOk()
            ->assertJsonPath('message', __('wallets::messages.sub_wallet_redistributed'))
            ->assertJsonPath('data.source.available_balance', '3000.00')
            ->assertJsonPath('data.destination.available_balance', '3000.00');

        $this->assertSame('10000.00', $wallet->fresh()->available_balance);

        $operation = WalletOperation::where('parent_wallet_id', $wallet->id)->latest()->firstOrFail();

        $this->assertSame('redistributed', $operation->type->value);
        $this->assertSame($source->id, $operation->from_sub_wallet_id);
        $this->assertSame($destination->id, $operation->to_sub_wallet_id);
    }

    public function test_redistribution_above_source_available_balance_is_rejected(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet());
        $source = $this->createSubWallet($wallet, ['available_balance' => 100]);
        $destination = $this->createSubWallet($wallet, ['available_balance' => 0]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/sub-wallets/'.$source->id.'/redistribute', [
                'destination_sub_wallet_id' => $destination->id,
                'amount' => 101,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.insufficient_sub_wallet_balance'));

        $this->assertSame('100.00', $source->fresh()->available_balance);
        $this->assertSame('0.00', $destination->fresh()->available_balance);
    }

    public function test_redistribution_to_the_same_wallet_is_rejected(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet());
        $subWallet = $this->createSubWallet($wallet, ['available_balance' => 100]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/sub-wallets/'.$subWallet->id.'/redistribute', [
                'destination_sub_wallet_id' => $subWallet->id,
                'amount' => 50,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.same_sub_wallet'));
    }

    public function test_redistribution_to_another_users_wallet_is_rejected(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $ownerWallet = $this->createWallet($owner, $this->fundedWallet());
        $otherWallet = $this->createWallet($other, $this->fundedWallet());
        $source = $this->createSubWallet($ownerWallet, ['available_balance' => 500]);
        $foreignDestination = $this->createSubWallet($otherWallet);

        $this->withHeaders($this->bearer($this->tokenFor($owner)))
            ->postJson('/api/v1/mobile/sub-wallets/'.$source->id.'/redistribute', [
                'destination_sub_wallet_id' => $foreignDestination->id,
                'amount' => 100,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.sub_wallet_not_found'));

        $this->assertSame('500.00', $source->fresh()->available_balance);
    }

    public function test_parent_wallet_response_includes_sub_wallet_summary_and_recent_operations(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet(20000));
        $subWallet = $this->createSubWallet($wallet, ['name' => 'Food']);
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/sub-wallets/'.$subWallet->id.'/allocate', ['amount' => 3000])
            ->assertOk();

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/parent-wallet')
            ->assertOk()
            ->assertJsonPath('data.sub_wallets_count', 1)
            ->assertJsonPath('data.allocated_to_sub_wallets', '3000.00')
            ->assertJsonPath('data.recent_operations.0.type', 'allocated')
            ->assertJsonPath('data.recent_operations.0.amount', '3000.00');
    }

    public function test_transfer_routes_require_authentication(): void
    {
        $this->postJson('/api/v1/mobile/sub-wallets/1/allocate', ['amount' => 10])->assertUnauthorized();
        $this->postJson('/api/v1/mobile/sub-wallets/1/redistribute', [
            'destination_sub_wallet_id' => 2,
            'amount' => 10,
        ])->assertUnauthorized();
    }

    private function fundedWallet(mixed $available = 20000): array
    {
        return [
            'total_allocated_balance' => $available,
            'available_balance' => $available,
            'status' => ParentWalletStatus::Active,
            'linked_at' => now(),
        ];
    }
}
