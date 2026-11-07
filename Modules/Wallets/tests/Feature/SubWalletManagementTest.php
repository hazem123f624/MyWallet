<?php

namespace Modules\Wallets\Tests\Feature;

use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Models\SubWallet;
use Modules\Wallets\Tests\Support\WalletsApiTestCase;

class SubWalletManagementTest extends WalletsApiTestCase
{
    public function test_user_can_create_and_view_sub_wallets(): void
    {
        $user = $this->createUser();
        $this->createWallet($user, $this->fundedWallet());
        $token = $this->tokenFor($user);

        $created = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/sub-wallets', [
                'name' => 'Electricity',
                'color' => '#7c3aed',
                'icon' => 'bolt',
            ])
            ->assertOk()
            ->assertJsonPath('message', __('wallets::messages.sub_wallet_created'))
            ->assertJsonPath('data.name', 'Electricity')
            ->assertJsonPath('data.available_balance', '0.00')
            ->assertJsonPath('data.reserved_balance', '0.00')
            ->assertJsonPath('data.total_balance', '0.00');

        $subWalletId = (int) $created->json('data.id');

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/sub-wallets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $subWalletId);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/sub-wallets/'.$subWalletId)
            ->assertOk()
            ->assertJsonPath('data.id', $subWalletId);
    }

    public function test_user_cannot_create_more_than_twelve_sub_wallets(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet());
        $token = $this->tokenFor($user);

        for ($index = 1; $index <= 12; $index++) {
            $this->withHeaders($this->bearer($token))
                ->postJson('/api/v1/mobile/sub-wallets', [
                    'name' => 'Wallet '.$index,
                    'color' => '#7c3aed',
                    'icon' => 'wallet',
                ])
                ->assertOk();
        }

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/sub-wallets', [
                'name' => 'Wallet 13',
                'color' => '#7c3aed',
                'icon' => 'wallet',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.sub_wallet_limit_reached', ['max' => 12]));

        $this->assertSame(12, SubWallet::where('parent_wallet_id', $wallet->id)->count());
    }

    public function test_renaming_a_sub_wallet_does_not_change_its_balance(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet());
        $subWallet = $this->createSubWallet($wallet, [
            'name' => 'Food',
            'available_balance' => 500,
            'reserved_balance' => 100,
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->patchJson('/api/v1/mobile/sub-wallets/'.$subWallet->id, [
                'name' => 'Groceries',
                'color' => '#059669',
                'icon' => 'utensils',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Groceries')
            ->assertJsonPath('data.available_balance', '500.00')
            ->assertJsonPath('data.reserved_balance', '100.00')
            ->assertJsonPath('data.total_balance', '600.00');

        $this->assertSame('500.00', $subWallet->fresh()->available_balance);
        $this->assertSame('100.00', $subWallet->fresh()->reserved_balance);
    }

    public function test_deleting_a_sub_wallet_returns_funds_to_the_parent_wallet(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet(15000));
        $subWallet = $this->createSubWallet($wallet, [
            'name' => 'Food',
            'available_balance' => 500,
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->deleteJson('/api/v1/mobile/sub-wallets/'.$subWallet->id)
            ->assertOk()
            ->assertJsonPath('message', __('wallets::messages.sub_wallet_deleted'));

        $this->assertSoftDeleted('sub_wallets', ['id' => $subWallet->id]);
        $this->assertSame('15500.00', $wallet->fresh()->available_balance);
        $this->assertDatabaseHas('wallet_operations', [
            'parent_wallet_id' => $wallet->id,
            'from_sub_wallet_id' => $subWallet->id,
            'type' => 'sub_wallet_returned',
            'amount' => 500,
        ]);
    }

    public function test_sub_wallet_with_reserved_funds_cannot_be_deleted(): void
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user, $this->fundedWallet());
        $subWallet = $this->createSubWallet($wallet, [
            'available_balance' => 100,
            'reserved_balance' => 50,
        ]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->deleteJson('/api/v1/mobile/sub-wallets/'.$subWallet->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.sub_wallet_has_reserved_funds'));

        $this->assertDatabaseHas('sub_wallets', ['id' => $subWallet->id, 'deleted_at' => null]);
    }

    public function test_user_cannot_access_another_users_sub_wallet(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $ownerWallet = $this->createWallet($owner, $this->fundedWallet());
        $otherWallet = $this->createWallet($other, $this->fundedWallet());
        $subWallet = $this->createSubWallet($ownerWallet);

        $token = $this->tokenFor($other);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/sub-wallets/'.$subWallet->id)
            ->assertNotFound();

        $this->withHeaders($this->bearer($token))
            ->patchJson('/api/v1/mobile/sub-wallets/'.$subWallet->id, ['name' => 'Stolen'])
            ->assertNotFound();

        $this->withHeaders($this->bearer($token))
            ->deleteJson('/api/v1/mobile/sub-wallets/'.$subWallet->id)
            ->assertNotFound();

        $this->assertDatabaseHas('sub_wallets', ['id' => $subWallet->id, 'parent_wallet_id' => $ownerWallet->id]);
        $this->assertNotSame($ownerWallet->id, $otherWallet->id);
    }

    public function test_invalid_color_or_icon_is_rejected(): void
    {
        $user = $this->createUser();
        $this->createWallet($user, $this->fundedWallet());
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/sub-wallets', [
                'name' => 'Invalid',
                'color' => '#000000',
                'icon' => 'unknown-icon',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['color', 'icon']);
    }

    public function test_sub_wallet_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/mobile/sub-wallets')->assertUnauthorized();
        $this->postJson('/api/v1/mobile/sub-wallets', [])->assertUnauthorized();
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
