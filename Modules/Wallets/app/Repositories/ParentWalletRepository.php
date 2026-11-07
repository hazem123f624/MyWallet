<?php

namespace Modules\Wallets\Repositories;

use Modules\Wallets\Interfaces\ParentWalletInterface;
use Modules\Wallets\Models\ParentWallet;

class ParentWalletRepository implements ParentWalletInterface
{
    public function __construct(private ParentWallet $model) {}

    public function findForUser(int $userId, bool $lock = false): ?ParentWallet
    {
        $query = $this->model
            ->newQuery()
            ->ownedBy($userId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function create(array $attributes): ParentWallet
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(ParentWallet $wallet, array $attributes): ParentWallet
    {
        $wallet->fill($attributes)->save();

        return $wallet;
    }
}
