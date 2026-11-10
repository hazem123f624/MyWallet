<?php

namespace Modules\Transfers\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Transfers\DTOs\BankingTransferResultDTO;
use Modules\Transfers\Enums\TransferEventType;
use Modules\Transfers\Enums\TransferReversalType;
use Modules\Transfers\Enums\TransferStatus;
use Modules\Transfers\Interfaces\BankingProviderInterface;
use Modules\Transfers\Interfaces\TransferInterface;
use Modules\Transfers\Models\Transfer;
use Modules\Transfers\Models\TransferEvent;
use Modules\Transfers\Models\TransferReversal;
use Modules\Wallets\Interfaces\TransferWalletInterface;

class TransferLifecycleService
{
    public function __construct(
        private TransferInterface $repository,
        private TransferWalletInterface $wallets,
        private BankingProviderInterface $provider,
    ) {}

    public function recordCreated(Transfer $transfer): void
    {
        $this->recordEvent(
            transfer: $transfer,
            type: TransferEventType::Created,
            fromStatus: null,
            toStatus: TransferStatus::Pending,
            message: 'Transfer request created and amount reserved.'
        );
    }

    public function recordCallbackReceived(Transfer $transfer, array $payload): void
    {
        $this->recordEvent(
            transfer: $transfer,
            type: TransferEventType::CallbackReceived,
            fromStatus: $transfer->status,
            toStatus: $transfer->status,
            message: 'Provider callback received.',
            providerReference: $payload['provider_reference'] ?? $transfer->provider_reference
        );
    }

    public function applyProviderResult(int $transferId, BankingTransferResultDTO $result, string $source = 'provider'): Transfer
    {
        return DB::transaction(function () use ($transferId, $result, $source): Transfer {
            $transfer = $this->repository->lockById($transferId);

            if (! $transfer) {
                throw (new ModelNotFoundException)->setModel(Transfer::class, [$transferId]);
            }

            $from = $transfer->status;
            $target = $result->status;

            if (! $this->isTransitionAllowed($from, $target)) {
                return $transfer;
            }

            if (in_array($target, [TransferStatus::Reversed, TransferStatus::Refunded], true)) {
                return $this->applyReversal($transfer, $target, $result, $source);
            }

            $attempts = (int) $transfer->check_attempts + 1;
            $attributes = [
                'status' => $target,
                'provider_reference' => $result->providerReference ?? $transfer->provider_reference,
                'metadata' => array_merge($transfer->metadata ?? [], $result->metadata),
                'last_checked_at' => now(),
                'check_attempts' => $attempts,
            ];

            if ($target === TransferStatus::Successful) {
                $this->wallets->settleReservation($transfer->user_id, $transfer->sub_wallet_id, $transfer->amount);
                $attributes['processed_at'] = now();
                $attributes['completed_at'] = now();
                $attributes['next_check_at'] = null;
            } elseif ($target === TransferStatus::Failed) {
                $this->wallets->releaseReservation($transfer->user_id, $transfer->sub_wallet_id, $transfer->amount);
                $attributes['failure_reason'] = $result->failureReason;
                $attributes['processed_at'] = now();
                $attributes['completed_at'] = now();
                $attributes['next_check_at'] = null;
            } else {
                $attributes['processed_at'] = $target === TransferStatus::Processing
                    ? ($transfer->processed_at ?? now())
                    : $transfer->processed_at;
                $attributes['next_check_at'] = now()->addMinutes($this->backoffMinutes($attempts));
            }

            $transfer = $this->repository->update($transfer, $attributes)->refresh();

            $this->recordEvent(
                transfer: $transfer,
                type: $this->eventTypeFor($target),
                fromStatus: $from,
                toStatus: $target,
                message: $result->failureReason ?? "Provider result: {$target->value} ({$source}).",
                providerReference: $result->providerReference
            );

            return $transfer->load(['events', 'reversal']);
        });
    }

    public function queryAndApply(Transfer $transfer, string $source = 'reconciliation'): Transfer
    {
        if (! in_array($transfer->status, [TransferStatus::Pending, TransferStatus::Processing], true)) {
            return $transfer;
        }

        try {
            $result = $this->provider->queryStatus(
                $transfer->provider_reference ?? $transfer->reference
            );
        } catch (\Throwable $exception) {
            logException($exception, 'Transfer status query failed.');

            return $this->markCheckFailed($transfer->id, __('transfers::messages.status_query_failed'));
        }

        return $this->applyProviderResult($transfer->id, $result, $source);
    }

    public function markCheckFailed(int $transferId, string $message): Transfer
    {
        return DB::transaction(function () use ($transferId, $message): Transfer {
            $transfer = $this->repository->lockById($transferId);

            if (! $transfer) {
                throw (new ModelNotFoundException)->setModel(Transfer::class, [$transferId]);
            }

            if (! in_array($transfer->status, [TransferStatus::Pending, TransferStatus::Processing], true)) {
                return $transfer;
            }

            $attempts = (int) $transfer->check_attempts + 1;
            $transfer = $this->repository->update($transfer, [
                'last_checked_at' => now(),
                'check_attempts' => $attempts,
                'next_check_at' => now()->addMinutes($this->backoffMinutes($attempts)),
            ])->refresh();

            $this->recordEvent(
                transfer: $transfer,
                type: TransferEventType::StatusChecked,
                fromStatus: $transfer->status,
                toStatus: $transfer->status,
                message: $message
            );

            return $transfer->load(['events', 'reversal']);
        });
    }

    public function reconcileDue(int $limit = 50): int
    {
        $checked = 0;

        foreach ($this->repository->dueForReconciliation($limit) as $transfer) {
            if ((int) $transfer->check_attempts >= (int) config('transfers.max_check_attempts', 20)) {
                continue;
            }

            $this->queryAndApply($transfer);
            $checked++;
        }

        return $checked;
    }

    private function applyReversal(Transfer $transfer, TransferStatus $target, BankingTransferResultDTO $result, string $source): Transfer
    {
        if (TransferReversal::where('transfer_id', $transfer->id)->exists()) {
            return $transfer;
        }

        $this->wallets->creditReversal($transfer->user_id, $transfer->sub_wallet_id, $transfer->amount);

        $type = $target === TransferStatus::Reversed
            ? TransferReversalType::Reversal
            : TransferReversalType::Refund;

        TransferReversal::create([
            'transfer_id' => $transfer->id,
            'type' => $type,
            'amount' => $transfer->amount,
            'currency' => $transfer->currency,
            'provider_reference' => $result->providerReference ?? $transfer->provider_reference,
            'reason' => $result->failureReason ?? $result->metadata['reason'] ?? null,
        ]);

        $transfer = $this->repository->update($transfer, [
            'status' => $target,
            'provider_reference' => $result->providerReference ?? $transfer->provider_reference,
            'metadata' => array_merge($transfer->metadata ?? [], $result->metadata),
            'last_checked_at' => now(),
            'next_check_at' => null,
            'reversed_at' => $target === TransferStatus::Reversed ? now() : $transfer->reversed_at,
            'refunded_at' => $target === TransferStatus::Refunded ? now() : $transfer->refunded_at,
        ])->refresh();

        $this->recordEvent(
            transfer: $transfer,
            type: $target === TransferStatus::Reversed ? TransferEventType::Reversed : TransferEventType::Refunded,
            fromStatus: TransferStatus::Successful,
            toStatus: $target,
            message: $result->failureReason ?? "Provider {$source} returned {$target->value}; amount returned to Sub Wallet.",
            providerReference: $result->providerReference
        );

        return $transfer;
    }

    private function isTransitionAllowed(TransferStatus $from, TransferStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        if (in_array($from, [TransferStatus::Pending, TransferStatus::Processing], true)) {
            return in_array($to, [TransferStatus::Pending, TransferStatus::Processing, TransferStatus::Successful, TransferStatus::Failed], true);
        }

        if ($from === TransferStatus::Successful) {
            return in_array($to, [TransferStatus::Reversed, TransferStatus::Refunded], true);
        }

        return false;
    }

    private function eventTypeFor(TransferStatus $status): TransferEventType
    {
        return match ($status) {
            TransferStatus::Processing => TransferEventType::Processing,
            TransferStatus::Successful => TransferEventType::Successful,
            TransferStatus::Failed => TransferEventType::Failed,
            TransferStatus::Reversed => TransferEventType::Reversed,
            TransferStatus::Refunded => TransferEventType::Refunded,
            default => TransferEventType::StatusChecked,
        };
    }

    private function recordEvent(
        Transfer $transfer,
        TransferEventType $type,
        ?TransferStatus $fromStatus,
        ?TransferStatus $toStatus,
        ?string $message = null,
        ?string $providerReference = null
    ): void {
        TransferEvent::create([
            'transfer_id' => $transfer->id,
            'type' => $type,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus?->value,
            'provider_reference' => $providerReference,
            'message' => $message,
        ]);
    }

    private function backoffMinutes(int $attempts): int
    {
        $base = max(1, (int) config('transfers.reconcile_interval_minutes', 5));
        $max = max($base, (int) config('transfers.max_backoff_minutes', 60));

        return min($max, max(1, $attempts * $base));
    }
}
