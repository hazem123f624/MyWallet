<?php

namespace Modules\Wallets\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Wallets\DTOs\SubWalletTransferSummaryDTO;
use Modules\Wallets\Interfaces\SubWalletInterface;
use Modules\Wallets\Interfaces\TransferWalletInterface;
use Modules\Wallets\Models\SubWallet;
use Modules\Wallets\Support\Money;

class TransferWalletService implements TransferWalletInterface
{
    public function __construct(
        private ParentWalletService $parentWalletService,
        private SubWalletInterface $subWallets,
    ) {}

    public function findSubWalletForUser(int $userId, int $subWalletId): ?SubWalletTransferSummaryDTO
    {
        $wallet = $this->parentWalletService->ensureForUser($userId);
        $subWallet = $this->subWallets->findForParent($wallet->id, $subWalletId);

        return $subWallet ? $this->toSummary($subWallet) : null;
    }

    public function reserveForTransfer(int $userId, int $subWalletId, string $amount): SubWalletTransferSummaryDTO
    {
        $amount = Money::normalize($amount);

        return DB::transaction(function () use ($userId, $subWalletId, $amount): SubWalletTransferSummaryDTO {
            $subWallet = $this->lockSubWallet($userId, $subWalletId);

            if (! Money::greaterThanOrEqual($subWallet->available_balance, $amount)) {
                throw ValidationException::withMessages([
                    'amount' => __('wallets::messages.insufficient_sub_wallet_balance'),
                ]);
            }

            $this->subWallets->update($subWallet, [
                'available_balance' => Money::subtract($subWallet->available_balance, $amount),
                'reserved_balance' => Money::add($subWallet->reserved_balance, $amount),
            ]);

            return $this->toSummary($subWallet->refresh()->load('parentWallet'));
        });
    }

    public function settleReservation(int $userId, int $subWalletId, string $amount): void
    {
        $amount = Money::normalize($amount);

        DB::transaction(function () use ($userId, $subWalletId, $amount): void {
            $subWallet = $this->lockSubWallet($userId, $subWalletId);

            if (! Money::greaterThanOrEqual($subWallet->reserved_balance, $amount)) {
                throw ValidationException::withMessages([
                    'amount' => __('wallets::messages.insufficient_reserved_balance'),
                ]);
            }

            $this->subWallets->update($subWallet, [
                'reserved_balance' => Money::subtract($subWallet->reserved_balance, $amount),
            ]);
        });
    }

    public function releaseReservation(int $userId, int $subWalletId, string $amount): void
    {
        $amount = Money::normalize($amount);

        DB::transaction(function () use ($userId, $subWalletId, $amount): void {
            $subWallet = $this->lockSubWallet($userId, $subWalletId);

            if (! Money::greaterThanOrEqual($subWallet->reserved_balance, $amount)) {
                throw ValidationException::withMessages([
                    'amount' => __('wallets::messages.insufficient_reserved_balance'),
                ]);
            }

            $this->subWallets->update($subWallet, [
                'reserved_balance' => Money::subtract($subWallet->reserved_balance, $amount),
                'available_balance' => Money::add($subWallet->available_balance, $amount),
            ]);
        });
    }

    public function creditReversal(int $userId, int $subWalletId, string $amount): void
    {
        $amount = Money::normalize($amount);

        DB::transaction(function () use ($userId, $subWalletId, $amount): void {
            $subWallet = $this->lockSubWallet($userId, $subWalletId);

            $this->subWallets->update($subWallet, [
                'available_balance' => Money::add($subWallet->available_balance, $amount),
            ]);
        });
    }

    private function lockSubWallet(int $userId, int $subWalletId): SubWallet
    {
        $wallet = $this->parentWalletService->ensureForUser($userId);
        $subWallet = $this->subWallets->findForParent($wallet->id, $subWalletId, true);

        if (! $subWallet) {
            throw (new ModelNotFoundException)->setModel(SubWallet::class, [$subWalletId]);
        }

        return $subWallet;
    }

    private function toSummary(SubWallet $subWallet): SubWalletTransferSummaryDTO
    {
        return new SubWalletTransferSummaryDTO(
            id: $subWallet->id,
            userId: $subWallet->parentWallet->user_id,
            parentWalletId: $subWallet->parent_wallet_id,
            name: $subWallet->name,
            availableBalance: $subWallet->available_balance,
            reservedBalance: $subWallet->reserved_balance,
            currency: $subWallet->parentWallet->currency,
            currencySymbol: $subWallet->parentWallet->currency_symbol,
        );
    }
}
