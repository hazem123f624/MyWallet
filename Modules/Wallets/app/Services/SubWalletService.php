<?php

namespace Modules\Wallets\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Wallets\Enums\WalletOperationType;
use Modules\Wallets\Interfaces\ParentWalletInterface;
use Modules\Wallets\Interfaces\SubWalletInterface;
use Modules\Wallets\Interfaces\WalletOperationInterface;
use Modules\Wallets\Models\ParentWallet;
use Modules\Wallets\Models\SubWallet;
use Modules\Wallets\Support\Money;

class SubWalletService
{
    public function __construct(
        private ParentWalletService $parentWalletService,
        private ParentWalletInterface $parentWallets,
        private SubWalletInterface $subWallets,
        private WalletOperationInterface $operations,
    ) {}

    public function list(int $userId): Collection
    {
        $wallet = $this->parentWalletService->ensureForUser($userId);

        return $this->subWallets->listForParent($wallet->id);
    }

    public function create(int $userId, array $data): SubWallet
    {
        return DB::transaction(function () use ($userId, $data): SubWallet {
            $wallet = $this->lockParentWallet($userId);
            $limit = (int) config('wallets.max_sub_wallets', 12);

            if ($this->subWallets->countForParent($wallet->id) >= $limit) {
                throw ValidationException::withMessages([
                    'name' => __('wallets::messages.sub_wallet_limit_reached', ['max' => $limit]),
                ]);
            }

            $subWallet = $this->subWallets->create([
                'parent_wallet_id' => $wallet->id,
                'name' => $data['name'],
                'color' => $data['color'],
                'icon' => $data['icon'],
                'available_balance' => Money::zero(),
                'reserved_balance' => Money::zero(),
            ]);

            return $subWallet->load('parentWallet');
        });
    }

    public function view(int $userId, int $subWalletId): SubWallet
    {
        $wallet = $this->parentWalletService->ensureForUser($userId);

        return $this->findOwnedOrFail($wallet->id, $subWalletId);
    }

    public function update(int $userId, int $subWalletId, array $data): SubWallet
    {
        return DB::transaction(function () use ($userId, $subWalletId, $data): SubWallet {
            $wallet = $this->lockParentWallet($userId);
            $subWallet = $this->findOwnedOrFail($wallet->id, $subWalletId, true);

            $attributes = array_intersect_key($data, array_flip(['name', 'color', 'icon']));
            $this->subWallets->update($subWallet, $attributes);

            return $subWallet->refresh()->load('parentWallet');
        });
    }

    public function delete(int $userId, int $subWalletId): void
    {
        DB::transaction(function () use ($userId, $subWalletId): void {
            $wallet = $this->lockParentWallet($userId);
            $subWallet = $this->findOwnedOrFail($wallet->id, $subWalletId, true);

            if (Money::isPositive($subWallet->reserved_balance)) {
                throw ValidationException::withMessages([
                    'sub_wallet' => __('wallets::messages.sub_wallet_has_reserved_funds'),
                ]);
            }

            $returned = $subWallet->available_balance;

            $this->parentWallets->update($wallet, [
                'available_balance' => Money::add($wallet->available_balance, $returned),
            ]);

            $this->subWallets->update($subWallet, [
                'available_balance' => Money::zero(),
                'reserved_balance' => Money::zero(),
            ]);

            if (Money::isPositive($returned)) {
                $this->recordOperation(
                    userId: $userId,
                    wallet: $wallet,
                    type: WalletOperationType::SubWalletReturned,
                    amount: $returned,
                    fromSubWalletId: $subWallet->id,
                    toSubWalletId: null,
                    description: "Returned from {$subWallet->name} to Parent Wallet"
                );
            }

            $this->subWallets->delete($subWallet);
        });
    }

    public function allocate(int $userId, int $subWalletId, mixed $amount): SubWallet
    {
        $amount = Money::normalize($amount);

        if (! Money::isPositive($amount)) {
            throw ValidationException::withMessages([
                'amount' => __('wallets::messages.amount_must_be_positive'),
            ]);
        }

        return DB::transaction(function () use ($userId, $subWalletId, $amount): SubWallet {
            $wallet = $this->lockParentWallet($userId);
            $subWallet = $this->findOwnedOrFail($wallet->id, $subWalletId, true);

            if (! Money::greaterThanOrEqual($wallet->available_balance, $amount)) {
                throw ValidationException::withMessages([
                    'amount' => __('wallets::messages.insufficient_parent_balance'),
                ]);
            }

            $this->parentWallets->update($wallet, [
                'available_balance' => Money::subtract($wallet->available_balance, $amount),
            ]);

            $this->subWallets->update($subWallet, [
                'available_balance' => Money::add($subWallet->available_balance, $amount),
            ]);

            $this->recordOperation(
                userId: $userId,
                wallet: $wallet,
                type: WalletOperationType::Allocated,
                amount: $amount,
                fromSubWalletId: null,
                toSubWalletId: $subWallet->id,
                description: "Allocated to {$subWallet->name}"
            );

            return $subWallet->refresh()->load('parentWallet');
        });
    }

    /**
     * @return array{source: SubWallet, destination: SubWallet}
     */
    public function redistribute(int $userId, int $sourceId, int $destinationId, mixed $amount): array
    {
        if ($sourceId === $destinationId) {
            throw ValidationException::withMessages([
                'destination_sub_wallet_id' => __('wallets::messages.same_sub_wallet'),
            ]);
        }

        $amount = Money::normalize($amount);

        if (! Money::isPositive($amount)) {
            throw ValidationException::withMessages([
                'amount' => __('wallets::messages.amount_must_be_positive'),
            ]);
        }

        return DB::transaction(function () use ($userId, $sourceId, $destinationId, $amount): array {
            $wallet = $this->lockParentWallet($userId);
            $locked = $this->subWallets->findManyForParentForUpdate($wallet->id, [$sourceId, $destinationId]);

            if ($locked->count() !== 2) {
                throw ValidationException::withMessages([
                    'sub_wallet' => __('wallets::messages.sub_wallet_not_found'),
                ]);
            }

            $source = $this->subWallets->findForParent($wallet->id, $sourceId);
            $destination = $this->subWallets->findForParent($wallet->id, $destinationId);

            if (! $source || ! $destination) {
                throw ValidationException::withMessages([
                    'sub_wallet' => __('wallets::messages.sub_wallet_not_found'),
                ]);
            }

            if (! Money::greaterThanOrEqual($source->available_balance, $amount)) {
                throw ValidationException::withMessages([
                    'amount' => __('wallets::messages.insufficient_sub_wallet_balance'),
                ]);
            }

            $this->subWallets->update($source, [
                'available_balance' => Money::subtract($source->available_balance, $amount),
            ]);

            $this->subWallets->update($destination, [
                'available_balance' => Money::add($destination->available_balance, $amount),
            ]);

            $this->recordOperation(
                userId: $userId,
                wallet: $wallet,
                type: WalletOperationType::Redistributed,
                amount: $amount,
                fromSubWalletId: $source->id,
                toSubWalletId: $destination->id,
                description: "Redistributed from {$source->name} to {$destination->name}"
            );

            return [
                'source' => $source->refresh()->load('parentWallet'),
                'destination' => $destination->refresh()->load('parentWallet'),
            ];
        });
    }

    private function lockParentWallet(int $userId): ParentWallet
    {
        $this->parentWalletService->ensureForUser($userId);

        return $this->parentWallets->findForUser($userId, true)
            ?? throw (new ModelNotFoundException)->setModel(ParentWallet::class);
    }

    private function findOwnedOrFail(int $parentWalletId, int $subWalletId, bool $lock = false): SubWallet
    {
        $subWallet = $this->subWallets->findForParent($parentWalletId, $subWalletId, $lock);

        if (! $subWallet) {
            throw (new ModelNotFoundException)->setModel(SubWallet::class, [$subWalletId]);
        }

        return $subWallet;
    }

    private function recordOperation(
        int $userId,
        ParentWallet $wallet,
        WalletOperationType $type,
        string $amount,
        ?int $fromSubWalletId,
        ?int $toSubWalletId,
        string $description
    ): void {
        $this->operations->create([
            'user_id' => $userId,
            'parent_wallet_id' => $wallet->id,
            'from_sub_wallet_id' => $fromSubWalletId,
            'to_sub_wallet_id' => $toSubWalletId,
            'type' => $type,
            'amount' => $amount,
            'currency' => $wallet->currency,
            'description' => $description,
        ]);
    }
}
