<?php

namespace Modules\Transfers\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Beneficiaries\Interfaces\BeneficiaryLookupInterface;
use Modules\Transfers\DTOs\BankingTransferRequestDTO;
use Modules\Transfers\DTOs\BankingTransferResultDTO;
use Modules\Transfers\Enums\TransferStatus;
use Modules\Transfers\Interfaces\BankingProviderInterface;
use Modules\Transfers\Interfaces\TransferInterface;
use Modules\Transfers\Models\Transfer;
use Modules\Transfers\Support\Money;
use Modules\Users\Interfaces\TransactionPinVerifierInterface;
use Modules\Wallets\Interfaces\TransferWalletInterface;

class TransferService
{
    public function __construct(
        private TransferInterface $repository,
        private BeneficiaryLookupInterface $beneficiaries,
        private TransferWalletInterface $wallets,
        private TransactionPinVerifierInterface $pinVerifier,
        private BankingProviderInterface $provider,
        private TransferLifecycleService $lifecycle,
    ) {}

    public function listForUser(int $userId)
    {
        return $this->repository->listForUser($userId);
    }

    public function historyForUser(int $userId, array $filters)
    {
        return $this->repository->historyForUser($userId, $filters);
    }

    public function viewForUser(int $userId, int $transferId): Transfer
    {
        $transfer = $this->repository->findForUser($userId, $transferId);

        if (! $transfer) {
            throw (new ModelNotFoundException)->setModel(Transfer::class, [$transferId]);
        }

        return $transfer->load(['events', 'reversal']);
    }

    public function limitsForUser(int $userId): array
    {
        $statuses = [TransferStatus::Pending->value, TransferStatus::Processing->value, TransferStatus::Successful->value];
        $dailyUsed = $this->repository->sumForUserBetween($userId, now()->startOfDay(), now(), $statuses);
        $monthlyUsed = $this->repository->sumForUserBetween($userId, now()->startOfMonth(), now(), $statuses);
        $dailyLimit = Money::normalize(config('transfers.daily_limit', 50000));
        $monthlyLimit = Money::normalize(config('transfers.monthly_limit', 200000));

        return [
            'min_amount' => Money::normalize(config('transfers.min_amount', 1)),
            'max_amount' => Money::normalize(config('transfers.max_amount', 100000)),
            'daily_limit' => $dailyLimit,
            'daily_used' => $dailyUsed,
            'daily_remaining' => Money::normalize(max(0, (float) $dailyLimit - (float) $dailyUsed)),
            'monthly_limit' => $monthlyLimit,
            'monthly_used' => $monthlyUsed,
            'monthly_remaining' => Money::normalize(max(0, (float) $monthlyLimit - (float) $monthlyUsed)),
            'currency' => null,
        ];
    }

    public function create(int $userId, array $data): Transfer
    {
        $amount = Money::normalize($data['amount']);

        if (! Money::isPositive($amount)) {
            throw ValidationException::withMessages([
                'amount' => __('transfers::messages.amount_must_be_positive'),
            ]);
        }

        $clientReference = $data['client_reference'] ?? null;

        if ($clientReference) {
            $existing = $this->repository->findByClientReference($userId, $clientReference);

            if ($existing) {
                return $existing;
            }
        }

        $beneficiary = $this->beneficiaries->findSummaryForUser($userId, (int) $data['beneficiary_id']);

        if (! $beneficiary || ! $beneficiary->isActive) {
            throw ValidationException::withMessages([
                'beneficiary_id' => __('transfers::messages.beneficiary_not_found'),
            ]);
        }

        $subWallet = $this->wallets->findSubWalletForUser($userId, (int) $data['sub_wallet_id']);

        if (! $subWallet) {
            throw ValidationException::withMessages([
                'sub_wallet_id' => __('transfers::messages.sub_wallet_not_found'),
            ]);
        }

        $min = (float) config('transfers.min_amount', 1);
        $max = (float) config('transfers.max_amount', 100000);

        if ((float) $amount < $min) {
            throw ValidationException::withMessages([
                'amount' => __('transfers::messages.amount_below_minimum', ['min' => Money::normalize($min)]),
            ]);
        }

        if ((float) $amount > $max) {
            throw ValidationException::withMessages([
                'amount' => __('transfers::messages.amount_above_maximum', ['max' => Money::normalize($max)]),
            ]);
        }

        $statuses = [TransferStatus::Pending->value, TransferStatus::Processing->value, TransferStatus::Successful->value];
        $dailyUsed = $this->repository->sumForUserBetween($userId, now()->startOfDay(), now(), $statuses);
        $monthlyUsed = $this->repository->sumForUserBetween($userId, now()->startOfMonth(), now(), $statuses);

        if (! Money::greaterThanOrEqual(config('transfers.daily_limit', 50000), Money::add($dailyUsed, $amount))) {
            throw ValidationException::withMessages([
                'amount' => __('transfers::messages.daily_limit_exceeded'),
            ]);
        }

        if (! Money::greaterThanOrEqual(config('transfers.monthly_limit', 200000), Money::add($monthlyUsed, $amount))) {
            throw ValidationException::withMessages([
                'amount' => __('transfers::messages.monthly_limit_exceeded'),
            ]);
        }

        $this->pinVerifier->verify($userId, (string) $data['transaction_pin']);

        try {
            $transfer = DB::transaction(function () use ($userId, $data, $amount, $clientReference, $beneficiary, $subWallet): Transfer {
                if ($clientReference) {
                    $existing = $this->repository->findByClientReference($userId, $clientReference);

                    if ($existing) {
                        return $existing;
                    }
                }

                $transfer = $this->repository->create([
                    'user_id' => $userId,
                    'parent_wallet_id' => $subWallet->parentWalletId,
                    'sub_wallet_id' => $subWallet->id,
                    'beneficiary_id' => $beneficiary->id,
                    'reference' => 'TRX-'.Str::upper(Str::ulid()),
                    'client_reference' => $clientReference,
                    'sub_wallet_name' => $subWallet->name,
                    'beneficiary_name' => $beneficiary->name,
                    'beneficiary_bank_name' => $beneficiary->bankName,
                    'beneficiary_account_last4' => substr($beneficiary->maskedAccountNumber, -4),
                    'amount' => $amount,
                    'currency' => $subWallet->currency,
                    'status' => TransferStatus::Pending,
                    'provider_name' => $this->provider->name(),
                    'note' => $data['note'] ?? null,
                    'requested_at' => now(),
                ]);

                $this->wallets->reserveForTransfer($userId, $subWallet->id, $amount);
                $this->lifecycle->recordCreated($transfer);

                return $transfer;
            });
        } catch (QueryException $exception) {
            if ($clientReference && (string) $exception->getCode() === '23000') {
                $existing = $this->repository->findByClientReference($userId, $clientReference);

                if ($existing) {
                    return $existing;
                }
            }

            throw $exception;
        }

        if ($transfer->status !== TransferStatus::Pending) {
            return $transfer;
        }

        try {
            $result = $this->provider->execute(new BankingTransferRequestDTO(
                reference: $transfer->reference,
                amount: $transfer->amount,
                currency: $transfer->currency,
                beneficiaryName: $transfer->beneficiary_name,
                beneficiaryBankName: $transfer->beneficiary_bank_name,
                beneficiaryAccountLast4: $transfer->beneficiary_account_last4,
            ));
        } catch (\Throwable $exception) {
            logException($exception, 'Banking provider transfer execution failed.');
            $result = new BankingTransferResultDTO(
                status: TransferStatus::Failed,
                failureReason: __('transfers::messages.provider_failed'),
            );
        }

        return $this->lifecycle->applyProviderResult($transfer->id, $result, 'initial_execution');
    }

    public function refreshForUser(int $userId, int $transferId): Transfer
    {
        return $this->lifecycle->queryAndApply($this->viewForUser($userId, $transferId), 'user_refresh');
    }

    public function syncAdminTransfer(int $transferId): Transfer
    {
        return $this->lifecycle->queryAndApply($this->adminView($transferId), 'admin_sync');
    }

    public function adminList(array $filters)
    {
        return $this->repository->adminData($filters);
    }

    public function adminView(int $transferId): Transfer
    {
        $transfer = $this->repository->findByIdForAdmin($transferId);

        if (! $transfer) {
            throw (new ModelNotFoundException)->setModel(Transfer::class, [$transferId]);
        }

        return $transfer->load(['events', 'reversal']);
    }

    public function statistics(): array
    {
        return $this->repository->statistics();
    }
}
