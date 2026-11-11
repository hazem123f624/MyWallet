<?php

namespace Modules\Wallets\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Wallets\Interfaces\SubWalletInterface;
use Modules\Wallets\Models\SubWallet;
use Modules\Wallets\Support\Money;

class SubWalletRepository implements SubWalletInterface
{
    public function __construct(private SubWallet $model) {}

    public function listForParent(int $parentWalletId): Collection
    {
        return $this->model
            ->newQuery()
            ->ownedByParent($parentWalletId)
            ->with('parentWallet')
            ->orderBy('created_at')
            ->get();
    }

    public function findForParent(int $parentWalletId, int $subWalletId, bool $lock = false): ?SubWallet
    {
        $query = $this->model
            ->newQuery()
            ->ownedByParent($parentWalletId)
            ->whereKey($subWalletId)
            ->with('parentWallet');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function findManyForParentForUpdate(int $parentWalletId, array $subWalletIds): Collection
    {
        return $this->model
            ->newQuery()
            ->ownedByParent($parentWalletId)
            ->whereIn('id', $subWalletIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function countForParent(int $parentWalletId): int
    {
        return $this->model
            ->newQuery()
            ->ownedByParent($parentWalletId)
            ->count();
    }

    public function summaryForParent(int $parentWalletId): array
    {
        $row = $this->model
            ->newQuery()
            ->ownedByParent($parentWalletId)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(available_balance + reserved_balance), 0) as allocated')
            ->first();

        return [
            'count' => (int) ($row->total ?? 0),
            'allocated' => Money::normalize($row->allocated ?? 0),
        ];
    }

    public function create(array $attributes): SubWallet
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(SubWallet $subWallet, array $attributes): SubWallet
    {
        $subWallet->fill($attributes)->save();

        return $subWallet;
    }

    public function delete(SubWallet $subWallet): void
    {
        $subWallet->delete();
    }
}
