<?php

namespace Modules\BankAccounts\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\BankAccounts\Models\BankAccount;

interface BankAccountInterface
{
    public function listForUser(int $userId): Collection;

    public function findForUser(int $userId, int $id, bool $lock = false): ?BankAccount;

    public function findByOwnerBankHash(int $userId, int $bankId, string $hash, bool $lock = false): ?BankAccount;

    public function create(array $attributes): BankAccount;

    public function update(BankAccount $account, array $attributes): BankAccount;

    public function delete(BankAccount $account): void;

    public function clearPrimary(int $userId, int $exceptId = 0): void;

    public function firstRemainingForUser(int $userId): ?BankAccount;

    public function hasPrimary(int $userId, int $exceptId = 0): bool;
}
