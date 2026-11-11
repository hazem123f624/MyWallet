<?php

namespace Modules\Wallets\Tests\Feature;

use Modules\Users\Events\UserAccountVerified;
use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Models\ParentWallet;
use Modules\Wallets\Tests\Support\WalletsApiTestCase;

class ParentWalletCreationTest extends WalletsApiTestCase
{
    public function test_account_verification_creates_a_parent_wallet(): void
    {
        $country = $this->createCountry('USD', '$');
        $user = $this->createUser(['country_id' => $country->id]);

        event(new UserAccountVerified($user->id, $country->id));

        $wallet = ParentWallet::where('user_id', $user->id)->first();

        $this->assertNotNull($wallet);
        $this->assertSame(ParentWalletStatus::Unlinked, $wallet->status);
        $this->assertSame('USD', $wallet->currency);
        $this->assertSame('$', $wallet->currency_symbol);
        $this->assertSame('0.00', $wallet->available_balance);
        $this->assertSame('0.00', $wallet->reserved_balance);
        $this->assertSame('0.00', $wallet->total_allocated_balance);
        $this->assertNull($wallet->bank_account_id);
    }

    public function test_wallet_creation_is_idempotent(): void
    {
        $user = $this->createUser();

        event(new UserAccountVerified($user->id, null));
        event(new UserAccountVerified($user->id, null));

        $this->assertSame(1, ParentWallet::where('user_id', $user->id)->count());
    }

    public function test_legacy_user_receives_a_wallet_on_first_request(): void
    {
        $user = $this->createUser();

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->getJson('/api/v1/mobile/parent-wallet')
            ->assertOk()
            ->assertJsonPath('data.status', ParentWalletStatus::Unlinked->value)
            ->assertJsonPath('data.currency', 'EGP')
            ->assertJsonPath('data.available_balance', '0.00')
            ->assertJsonPath('data.reserved_balance', '0.00')
            ->assertJsonPath('data.total_allocated_balance', '0.00')
            ->assertJsonPath('data.funding_source', null);

        $this->assertDatabaseHas('parent_wallets', ['user_id' => $user->id]);
    }

    public function test_unauthenticated_user_cannot_view_the_wallet(): void
    {
        $this->getJson('/api/v1/mobile/parent-wallet')->assertUnauthorized();
    }

    public function test_suspended_user_cannot_view_the_wallet(): void
    {
        $user = $this->createUser(['is_active' => false]);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->getJson('/api/v1/mobile/parent-wallet')
            ->assertForbidden()
            ->assertJsonPath('message', __('users::messages.account_suspended'));
    }

    public function test_backfill_command_creates_wallets_for_existing_users(): void
    {
        $userOne = $this->createUser();
        $userTwo = $this->createUser();

        $this->artisan('wallets:backfill')->assertSuccessful();

        $this->assertDatabaseHas('parent_wallets', ['user_id' => $userOne->id]);
        $this->assertDatabaseHas('parent_wallets', ['user_id' => $userTwo->id]);
    }
}
