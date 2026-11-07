<?php

namespace Modules\BankAccounts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\BankAccounts\Interfaces\BankAccountInterface;
use Modules\BankAccounts\Models\BankAccount;

class BankAccountRepository implements BankAccountInterface
{
    public function __construct(private BankAccount $model) {}

    public function listForUser(int $userId): Collection
    {
        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->with('bank')
            ->orderByDesc('is_primary')
            ->orderBy('created_at')
            ->get();
    }

    public function findForUser(int $userId, int $id, bool $lock = false): ?BankAccount
    {
        $query = $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->whereKey($id);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function findByOwnerBankHash(int $userId, int $bankId, string $hash, bool $lock = false): ?BankAccount
    {
        $query = $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->where('bank_id', $bankId)
            ->where('account_number_hash', $hash);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function create(array $attributes): BankAccount
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(BankAccount $account, array $attributes): BankAccount
    {
        $account->fill($attributes)->save();

        return $account;
    }

    public function delete(BankAccount $account): void
    {
        $account->delete();
    }

    public function clearPrimary(int $userId, int $exceptId = 0): void
    {
        $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->where('is_primary', true)
            ->when($exceptId > 0, fn ($query) => $query->whereKeyNot($exceptId))
            ->update(['is_primary' => false]);
    }

    public function firstRemainingForUser(int $userId): ?BankAccount
    {
        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->orderByDesc('status')
            ->orderBy('created_at')
            ->first();
    }

    public function hasPrimary(int $userId, int $exceptId = 0): bool
    {
        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->where('is_primary', true)
            ->when($exceptId > 0, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
