<?php

namespace Modules\Wallets\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function paginateForUser(int $userId, array $filters): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->with(['fromSubWallet', 'toSubWallet'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) ($filters['per_page'] ?? 10))));
    }
}
