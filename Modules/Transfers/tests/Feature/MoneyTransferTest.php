<?php

namespace Modules\Transfers\Tests\Feature;

use Modules\Transfers\DTOs\BankingTransferResultDTO;
use Modules\Transfers\Enums\TransferStatus;
use Modules\Transfers\Interfaces\BankingProviderInterface;
use Modules\Transfers\Models\Transfer;
use Modules\Transfers\Tests\Support\FakeBankingProvider;
use Modules\Transfers\Tests\Support\TransfersApiTestCase;

class MoneyTransferTest extends TransfersApiTestCase
{
    public function test_successful_transfer_settles_the_reserved_amount(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 200,
                'transaction_pin' => '2468',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value)
            ->assertJsonPath('data.amount', '200.00');

        $subWallet->refresh();

        $this->assertSame('800.00', $subWallet->available_balance);
        $this->assertSame('0.00', $subWallet->reserved_balance);
        $this->assertDatabaseHas('transfers', [
            'user_id' => $user->id,
            'sub_wallet_id' => $subWallet->id,
            'status' => TransferStatus::Successful->value,
        ]);
    }

    public function test_failed_transfer_releases_the_reserved_amount(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Failed, failureReason: 'Declined by bank');

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 200,
                'transaction_pin' => '2468',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Failed->value)
            ->assertJsonPath('data.failure_reason', 'Declined by bank');

        $subWallet->refresh();

        $this->assertSame('1000.00', $subWallet->available_balance);
        $this->assertSame('0.00', $subWallet->reserved_balance);
    }

    public function test_pending_transfer_keeps_the_amount_reserved(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 200,
                'transaction_pin' => '2468',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Processing->value);

        $subWallet->refresh();

        $this->assertSame('800.00', $subWallet->available_balance);
        $this->assertSame('200.00', $subWallet->reserved_balance);
    }

    public function test_transfer_requires_the_correct_transaction_pin(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->tokenFor($user);

        $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 100,
                'transaction_pin' => '0000',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('users::messages.transaction_pin_incorrect'));

        $this->assertSame(0, Transfer::count());
        $this->assertSame('1000.00', $subWallet->fresh()->available_balance);
    }

    public function test_transfer_rejects_insufficient_balance_before_provider_call(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 1500,
                'transaction_pin' => '2468',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('wallets::messages.insufficient_sub_wallet_balance'));

        $this->assertSame(0, Transfer::count());
        $this->assertSame('1000.00', $subWallet->fresh()->available_balance);
    }

    public function test_transfer_rejects_daily_limit_violations(): void
    {
        config(['transfers.daily_limit' => 100]);
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 200,
                'transaction_pin' => '2468',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('transfers::messages.daily_limit_exceeded'));

        $this->assertSame(0, Transfer::count());
    }

    public function test_user_cannot_transfer_from_another_users_sub_wallet(): void
    {
        [$owner, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $other = $this->createUser();
        $otherBeneficiary = $this->createBeneficiary($other);

        $this->withHeaders($this->bearer($this->tokenFor($other)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $otherBeneficiary->id,
                'amount' => 100,
                'transaction_pin' => '2468',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('transfers::messages.sub_wallet_not_found'));
    }

    public function test_user_cannot_use_another_users_beneficiary(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $other = $this->createUser();
        $otherBeneficiary = $this->createBeneficiary($other);

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $otherBeneficiary->id,
                'amount' => 100,
                'transaction_pin' => '2468',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', __('transfers::messages.beneficiary_not_found'));
    }

    public function test_client_reference_makes_transfer_retries_idempotent(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->tokenFor($user);
        $payload = [
            'sub_wallet_id' => $subWallet->id,
            'beneficiary_id' => $beneficiary->id,
            'amount' => 200,
            'transaction_pin' => '2468',
            'client_reference' => 'transfer-attempt-1',
        ];

        $first = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/transfers', $payload)
            ->assertOk();

        $second = $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/transfers', $payload)
            ->assertOk();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Transfer::count());
        $this->assertSame('800.00', $subWallet->fresh()->available_balance);
    }

    public function test_user_can_view_recent_transfers_and_limits(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->tokenFor($user);

        $id = (int) $this->withHeaders($this->bearer($token))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 100,
                'transaction_pin' => '2468',
            ])
            ->assertOk()
            ->json('data.id');

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/'.$id)
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        $this->withHeaders($this->bearer($token))
            ->getJson('/api/v1/mobile/transfers/limits')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['min_amount', 'max_amount', 'daily_limit', 'daily_used', 'daily_remaining', 'monthly_limit', 'monthly_used', 'monthly_remaining'],
            ]);
    }

    public function test_admin_can_monitor_transfers(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', [
                'sub_wallet_id' => $subWallet->id,
                'beneficiary_id' => $beneficiary->id,
                'amount' => 100,
                'transaction_pin' => '2468',
            ])
            ->assertOk();

        $admin = $this->createAdmin();
        $adminToken = $admin->createToken('admins-test', ['admins'])->plainTextToken;

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/transfers')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/transfers/statistics')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.successful', 1);

        $transfer = Transfer::firstOrFail();

        $this->withHeaders($this->bearer($adminToken))
            ->getJson('/api/v1/dashboard/transfers/'.$transfer->id)
            ->assertOk()
            ->assertJsonPath('data.id', $transfer->id);
    }

    public function test_transfer_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/mobile/transfers')->assertUnauthorized();
        $this->postJson('/api/v1/mobile/transfers', [])->assertUnauthorized();
    }

    private function useProvider(TransferStatus $status, ?string $failureReason = null): void
    {
        app()->instance(BankingProviderInterface::class, new FakeBankingProvider(
            new BankingTransferResultDTO(
                status: $status,
                providerReference: 'FAKE-REFERENCE',
                failureReason: $failureReason,
            )
        ));
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
