<?php

namespace Modules\Beneficiaries\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Beneficiaries\DTOs\BeneficiarySummaryDTO;
use Modules\Beneficiaries\Interfaces\BeneficiaryInterface;
use Modules\Beneficiaries\Interfaces\BeneficiaryLookupInterface;
use Modules\Beneficiaries\Models\Beneficiary;

class BeneficiaryRepository implements BeneficiaryInterface, BeneficiaryLookupInterface
{
    public function __construct(private Beneficiary $model) {}

    public function listForUser(int $userId): Collection
    {
        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->latest()
            ->get();
    }

    public function findForUser(int $userId, int $beneficiaryId, bool $lock = false): ?Beneficiary
    {
        $query = $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->whereKey($beneficiaryId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function findByOwnerHash(int $userId, string $hash): ?Beneficiary
    {
        return $this->model
            ->withTrashed()
            ->ownedBy($userId)
            ->where('account_number_hash', $hash)
            ->first();
    }

    public function adminData(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->model
            ->newQuery()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_number_last4', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', (int) $filters['user_id']))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function create(array $attributes): Beneficiary
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Beneficiary $beneficiary, array $attributes): Beneficiary
    {
        $beneficiary->fill($attributes)->save();

        return $beneficiary;
    }

    public function delete(Beneficiary $beneficiary): void
    {
        $beneficiary->delete();
    }

    public function findSummaryForUser(int $userId, int $beneficiaryId): ?BeneficiarySummaryDTO
    {
        $beneficiary = $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->find($beneficiaryId);

        return $beneficiary ? new BeneficiarySummaryDTO(
            id: $beneficiary->id,
            userId: $beneficiary->user_id,
            name: $beneficiary->name,
            bankName: $beneficiary->bank_name,
            bankCode: $beneficiary->bank_code,
            maskedAccountNumber: '****'.$beneficiary->account_number_last4,
            maskedIban: $beneficiary->iban
                ? str_repeat('*', max(0, strlen($beneficiary->iban) - 4)).substr($beneficiary->iban, -4)
                : null,
            isActive: (bool) $beneficiary->is_active,
        ) : null;
    }
}
