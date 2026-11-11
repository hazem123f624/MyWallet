<?php

namespace Modules\Wallets\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\Wallets\Models\SubWallet;

interface SubWalletInterface
{
    public function listForParent(int $parentWalletId): Collection;

    public function findForParent(int $parentWalletId, int $subWalletId, bool $lock = false): ?SubWallet;

    public function findManyForParentForUpdate(int $parentWalletId, array $subWalletIds): Collection;

    public function countForParent(int $parentWalletId): int;

    /**
     * @return array{count: int, allocated: string}
     */
    public function summaryForParent(int $parentWalletId): array;

    public function create(array $attributes): SubWallet;

    public function update(SubWallet $subWallet, array $attributes): SubWallet;

    public function delete(SubWallet $subWallet): void;
}
