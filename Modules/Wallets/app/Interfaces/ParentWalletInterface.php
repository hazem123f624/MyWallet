<?php

namespace Modules\Wallets\Interfaces;

use Modules\Wallets\Models\ParentWallet;

interface ParentWalletInterface
{
    public function findForUser(int $userId, bool $lock = false): ?ParentWallet;

    public function create(array $attributes): ParentWallet;

    public function update(ParentWallet $wallet, array $attributes): ParentWallet;
}
