<?php

namespace Modules\Wallets\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Wallets\Interfaces\WalletOperationInterface;
use Modules\Wallets\Models\WalletOperation;

class WalletOperationRepository implements WalletOperationInterface
{
    public function __construct(private WalletOperation $model) {}

    public function create(array $attributes): WalletOperation
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function recentForParent(int $parentWalletId, int $limit = 10): Collection
    {
        return $this->model
            ->newQuery()
            ->where('parent_wallet_id', $parentWalletId)
            ->with(['fromSubWallet', 'toSubWallet'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
