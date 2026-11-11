<?php

namespace Modules\Wallets\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Wallets\Models\WalletOperation;

interface WalletOperationInterface
{
    public function create(array $attributes): WalletOperation;

    public function recentForParent(int $parentWalletId, int $limit = 10): Collection;

    public function paginateForUser(int $userId, array $filters): LengthAwarePaginator;
}
