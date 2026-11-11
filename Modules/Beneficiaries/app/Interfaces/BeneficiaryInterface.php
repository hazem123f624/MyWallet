<?php

namespace Modules\Beneficiaries\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Beneficiaries\Models\Beneficiary;

interface BeneficiaryInterface
{
    public function listForUser(int $userId): Collection;

    public function findForUser(int $userId, int $beneficiaryId, bool $lock = false): ?Beneficiary;

    public function findByOwnerHash(int $userId, string $hash): ?Beneficiary;

    public function adminData(array $filters): LengthAwarePaginator;

    public function create(array $attributes): Beneficiary;

    public function update(Beneficiary $beneficiary, array $attributes): Beneficiary;

    public function delete(Beneficiary $beneficiary): void;
}
