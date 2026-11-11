<?php

namespace Modules\Transfers\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Transfers\DTOs\BankingTransferRequestDTO;
use Modules\Transfers\DTOs\BankingTransferResultDTO;
use Modules\Transfers\Enums\TransferEventType;
use Modules\Transfers\Enums\TransferStatus;
use Modules\Transfers\Interfaces\BankingProviderInterface;
use Modules\Transfers\Models\Transfer;
use Modules\Transfers\Models\TransferEvent;
use Modules\Transfers\Models\TransferReversal;
use Modules\Transfers\Tests\Support\FakeBankingProvider;
use Modules\Transfers\Tests\Support\TransfersApiTestCase;

class TransferLifecycleTest extends TransfersApiTestCase
{
    public function test_transfer_records_created_and_successful_events(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->bearer($this->tokenFor($user));

        $id = (int) $this->withHeaders($token)
            ->postJson('/api/v1/mobile/transfers', $this->payload($subWallet, $beneficiary))
            ->assertOk()
            ->json('data.id');

        $this->withHeaders($token)
            ->getJson('/api/v1/mobile/transfers/'.$id)
            ->assertOk()
            ->assertJsonPath('data.events.0.type', TransferEventType::Successful->value)
            ->assertJsonPath('data.events.1.type', TransferEventType::Created->value)
            ->assertJsonPath('data.events.0.to_status', TransferStatus::Successful->value);

        $this->assertSame(2, TransferEvent::where('transfer_id', $id)->count());
    }

    public function test_user_can_refresh_a_processing_transfer(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $token = $this->bearer($this->tokenFor($user));
        $id = (int) $this->withHeaders($token)
            ->postJson('/api/v1/mobile/transfers', $this->payload($subWallet, $beneficiary))
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Processing->value)
            ->json('data.id');

        $this->useProvider(TransferStatus::Successful, providerReference: 'LIVE-REF-100');

        $this->withHeaders($token)
            ->postJson('/api/v1/mobile/transfers/'.$id.'/refresh')
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value)
            ->assertJsonPath('data.provider_reference', 'LIVE-REF-100')
            ->assertJsonPath('data.events.0.type', TransferEventType::Successful->value);

        $subWallet->refresh();

        $this->assertSame('800.00', $subWallet->available_balance);
        $this->assertSame('0.00', $subWallet->reserved_balance);
        $this->assertSame(2, Transfer::findOrFail($id)->check_attempts);
    }

    public function test_user_refresh_does_not_change_terminal_transfers(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $token = $this->bearer($this->tokenFor($user));

        $id = (int) $this->withHeaders($token)
            ->postJson('/api/v1/mobile/transfers', $this->payload($subWallet, $beneficiary))
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value)
            ->json('data.id');

        $this->useProvider(TransferStatus::Reversed);

        $this->withHeaders($token)
            ->postJson('/api/v1/mobile/transfers/'.$id.'/refresh')
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value);

        $this->assertSame(0, TransferReversal::where('transfer_id', $id)->count());
        $this->assertSame('800.00', $subWallet->fresh()->available_balance);
    }

    public function test_provider_callback_completes_a_processing_transfer(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);

        $this->postCallback([
            'reference' => $transfer->reference,
            'status' => TransferStatus::Successful->value,
            'provider_reference' => 'CALLBACK-REF-1',
            'amount' => '200.00',
        ])->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value);

        $transfer->refresh();
        $subWallet->refresh();

        $this->assertSame(TransferStatus::Successful, $transfer->status);
        $this->assertSame('CALLBACK-REF-1', $transfer->provider_reference);
        $this->assertSame('800.00', $subWallet->available_balance);
        $this->assertSame('0.00', $subWallet->reserved_balance);
        $this->assertTrue($transfer->events()->where('type', TransferEventType::CallbackReceived->value)->exists());
    }

    public function test_provider_callback_failure_releases_the_reservation(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);

        $this->postCallback([
            'reference' => $transfer->reference,
            'status' => TransferStatus::Failed->value,
            'amount' => '200.00',
            'reason' => 'Beneficiary account closed',
        ])->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Failed->value);

        $transfer->refresh();
        $subWallet->refresh();

        $this->assertSame(TransferStatus::Failed, $transfer->status);
        $this->assertSame('Beneficiary account closed', $transfer->failure_reason);
        $this->assertSame('1000.00', $subWallet->available_balance);
        $this->assertSame('0.00', $subWallet->reserved_balance);
    }

    public function test_provider_callback_rejects_when_the_secret_is_not_configured(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);

        config(['transfers.callback_secret' => null]);

        $content = json_encode([
            'reference' => $transfer->reference,
            'status' => TransferStatus::Successful->value,
        ]);

        Cache::flush();

        $this->call(
            'POST',
            '/api/v1/provider/transfers/callback',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_PROVIDER_SIGNATURE' => hash_hmac('sha256', $content, 'anything'),
            ],
            $content
        )->assertForbidden();

        $this->assertSame(TransferStatus::Processing, $transfer->fresh()->status);
    }

    public function test_provider_callback_can_find_a_transfer_by_provider_reference(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);

        $this->postCallback([
            'reference' => $transfer->provider_reference,
            'status' => TransferStatus::Successful->value,
            'amount' => '200.00',
        ])->assertOk()
            ->assertJsonPath('data.reference', $transfer->reference)
            ->assertJsonPath('data.status', TransferStatus::Successful->value);
    }

    public function test_provider_callback_rejects_an_invalid_signature(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);

        $this->postCallback([
            'reference' => $transfer->reference,
            'status' => TransferStatus::Successful->value,
        ], 'wrong-signature')->assertForbidden();

        $this->assertSame(TransferStatus::Processing, $transfer->fresh()->status);
    }

    public function test_provider_callback_rejects_a_mismatched_amount(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);

        $this->postCallback([
            'reference' => $transfer->reference,
            'status' => TransferStatus::Successful->value,
            'amount' => '999.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertSame(TransferStatus::Processing, $transfer->fresh()->status);
    }

    public function test_provider_reversal_credits_the_sub_wallet_once(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $transfer = $this->createSuccessfulTransfer($user, $subWallet, $beneficiary);

        $this->assertSame('800.00', $subWallet->fresh()->available_balance);

        $payload = [
            'reference' => $transfer->reference,
            'status' => TransferStatus::Reversed->value,
            'provider_reference' => 'REVERSAL-REF-1',
            'amount' => '200.00',
            'reason' => 'Recipient bank returned the funds',
        ];

        $this->postCallback($payload)->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Reversed->value);

        $this->postCallback($payload)->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Reversed->value);

        $transfer->refresh();
        $subWallet->refresh();

        $this->assertSame(TransferStatus::Reversed, $transfer->status);
        $this->assertSame('1000.00', $subWallet->available_balance);
        $this->assertSame('0.00', $subWallet->reserved_balance);
        $this->assertNotNull($transfer->reversed_at);
        $this->assertSame(1, TransferReversal::where('transfer_id', $transfer->id)->count());
        $this->assertDatabaseHas('transfer_reversals', [
            'transfer_id' => $transfer->id,
            'type' => 'reversal',
            'amount' => '200.00',
        ]);
    }

    public function test_provider_refund_credits_the_sub_wallet_once(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $transfer = $this->createSuccessfulTransfer($user, $subWallet, $beneficiary);

        $payload = [
            'reference' => $transfer->reference,
            'status' => TransferStatus::Refunded->value,
            'amount' => '200.00',
            'reason' => 'Refund approved',
        ];

        $this->postCallback($payload)->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Refunded->value);

        $this->postCallback($payload)->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Refunded->value);

        $transfer->refresh();
        $subWallet->refresh();

        $this->assertSame(TransferStatus::Refunded, $transfer->status);
        $this->assertSame('1000.00', $subWallet->available_balance);
        $this->assertNotNull($transfer->refunded_at);
        $this->assertSame(1, TransferReversal::where('transfer_id', $transfer->id)->count());
        $this->assertDatabaseHas('transfer_reversals', [
            'transfer_id' => $transfer->id,
            'type' => 'refund',
        ]);
    }

    public function test_user_refresh_requires_authentication(): void
    {
        $this->postJson('/api/v1/mobile/transfers/1/refresh')->assertUnauthorized();
    }

    public function test_admin_sync_does_not_change_a_terminal_transfer(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $transfer = $this->createSuccessfulTransfer($user, $subWallet, $beneficiary);
        $adminToken = $this->bearer($this->createAdmin()->createToken('transfers-test', ['admins'])->plainTextToken);

        $this->useProvider(TransferStatus::Reversed);

        $this->withHeaders($adminToken)
            ->postJson('/api/v1/dashboard/transfers/'.$transfer->id.'/sync')
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value);

        $this->assertSame(TransferStatus::Successful, $transfer->fresh()->status);
        $this->assertSame(0, TransferReversal::where('transfer_id', $transfer->id)->count());
    }

    public function test_admin_can_sync_a_processing_transfer(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);
        $adminToken = $this->bearer($this->createAdmin()->createToken('transfers-test', ['admins'])->plainTextToken);

        $this->useProvider(TransferStatus::Successful, providerReference: 'ADMIN-SYNC-REF');

        $this->withHeaders($adminToken)
            ->postJson('/api/v1/dashboard/transfers/'.$transfer->id.'/sync')
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value)
            ->assertJsonPath('data.provider_reference', 'ADMIN-SYNC-REF')
            ->assertJsonPath('data.events.0.type', TransferEventType::Successful->value);

        $this->assertSame('800.00', $subWallet->fresh()->available_balance);
    }

    public function test_reconciliation_command_processes_due_transfers(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);
        $transfer->update(['next_check_at' => now()->subMinute()]);

        $this->useProvider(TransferStatus::Successful);

        $this->artisan('transfers:reconcile')
            ->expectsOutputToContain('Checked: 1')
            ->assertSuccessful();

        $this->assertSame(TransferStatus::Successful, $transfer->fresh()->status);
        $this->assertSame('800.00', $subWallet->fresh()->available_balance);
    }

    public function test_reconciliation_skips_transfers_that_are_not_due(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);
        $transfer->update(['next_check_at' => now()->addMinutes(10)]);

        $this->useProvider(TransferStatus::Successful);

        $this->artisan('transfers:reconcile')
            ->expectsOutputToContain('Checked: 0')
            ->assertSuccessful();

        $this->assertSame(TransferStatus::Processing, $transfer->fresh()->status);
    }

    public function test_reconciliation_skips_transfers_that_reached_the_attempt_limit(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);
        $transfer->update([
            'next_check_at' => now()->subMinute(),
            'check_attempts' => (int) config('transfers.max_check_attempts', 20),
        ]);

        $this->useProvider(TransferStatus::Successful);

        $this->artisan('transfers:reconcile')
            ->expectsOutputToContain('Checked: 0')
            ->assertSuccessful();

        $this->assertSame(TransferStatus::Processing, $transfer->fresh()->status);
    }

    public function test_reconciliation_schedules_a_retry_when_the_provider_fails(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();
        $this->useProvider(TransferStatus::Processing);

        $transfer = $this->createProcessingTransfer($user, $subWallet, $beneficiary);
        $transfer->update(['next_check_at' => now()->subMinute()]);

        app()->instance(BankingProviderInterface::class, new class implements BankingProviderInterface
        {
            public function name(): string
            {
                return 'failing';
            }

            public function execute(BankingTransferRequestDTO $request): BankingTransferResultDTO
            {
                throw new \RuntimeException('Provider unavailable');
            }

            public function queryStatus(string $providerReference): BankingTransferResultDTO
            {
                throw new \RuntimeException('Provider unavailable');
            }
        });

        $this->artisan('transfers:reconcile')->assertSuccessful();

        $transfer->refresh();

        $this->assertSame(TransferStatus::Processing, $transfer->status);
        $this->assertSame(2, $transfer->check_attempts);
        $this->assertTrue($transfer->next_check_at->isFuture());
        $this->assertTrue($transfer->events()->where('type', TransferEventType::StatusChecked->value)->exists());
    }

    public function test_admin_transfer_statistics_include_reversed_and_refunded(): void
    {
        [$user, $wallet, $subWallet, $beneficiary] = $this->fixture();

        $reversed = $this->createSuccessfulTransfer($user, $subWallet, $beneficiary);
        $refunded = $this->createSuccessfulTransfer($user, $subWallet, $beneficiary);

        $this->postCallback([
            'reference' => $reversed->reference,
            'status' => TransferStatus::Reversed->value,
            'amount' => '200.00',
        ])->assertOk();

        $this->postCallback([
            'reference' => $refunded->reference,
            'status' => TransferStatus::Refunded->value,
            'amount' => '200.00',
        ])->assertOk();

        $adminToken = $this->bearer($this->createAdmin()->createToken('transfers-test', ['admins'])->plainTextToken);

        $this->withHeaders($adminToken)
            ->getJson('/api/v1/dashboard/transfers/statistics')
            ->assertOk()
            ->assertJsonPath('data.reversed', 1)
            ->assertJsonPath('data.refunded', 1)
            ->assertJsonPath('data.refunded_volume', '400.00');
    }

    private function fixture(): array
    {
        $user = $this->createUser();
        $wallet = $this->createWallet($user);
        $subWallet = $this->createSubWallet($wallet);
        $beneficiary = $this->createBeneficiary($user);

        return [$user, $wallet, $subWallet, $beneficiary];
    }

    private function payload($subWallet, $beneficiary): array
    {
        return [
            'sub_wallet_id' => $subWallet->id,
            'beneficiary_id' => $beneficiary->id,
            'amount' => 200,
            'transaction_pin' => '2468',
        ];
    }

    private function createSuccessfulTransfer($user, $subWallet, $beneficiary): Transfer
    {
        $this->useProvider(TransferStatus::Successful, providerReference: 'SUCCESS-REF-1');

        $id = (int) $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', $this->payload($subWallet, $beneficiary))
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Successful->value)
            ->json('data.id');

        return Transfer::findOrFail($id);
    }

    private function createProcessingTransfer($user, $subWallet, $beneficiary): Transfer
    {
        $id = (int) $this->withHeaders($this->bearer($this->tokenFor($user)))
            ->postJson('/api/v1/mobile/transfers', $this->payload($subWallet, $beneficiary))
            ->assertOk()
            ->assertJsonPath('data.status', TransferStatus::Processing->value)
            ->json('data.id');

        return Transfer::findOrFail($id);
    }

    private function postCallback(array $payload, ?string $signature = null)
    {
        config(['transfers.callback_secret' => 'phase-6-callback-secret']);

        $content = json_encode($payload);
        $signature ??= hash_hmac('sha256', $content, 'phase-6-callback-secret');

        Cache::flush();

        return $this->call(
            'POST',
            '/api/v1/provider/transfers/callback',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_PROVIDER_SIGNATURE' => $signature,
            ],
            $content
        );
    }

    private function useProvider(TransferStatus $status, ?string $providerReference = 'FAKE-REFERENCE'): void
    {
        app()->instance(BankingProviderInterface::class, new FakeBankingProvider(
            new BankingTransferResultDTO(
                status: $status,
                providerReference: $providerReference,
            )
        ));
    }
}
