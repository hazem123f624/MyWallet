<?php

namespace Modules\Transfers\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Modules\Transfers\Models\Transfer;

interface TransferInterface
{
    public function listForUser(int $userId): Collection;

    public function historyForUser(int $userId, array $filters): LengthAwarePaginator;

    public function findForUser(int $userId, int $transferId): ?Transfer;

    public function findByClientReference(int $userId, string $clientReference): ?Transfer;

    public function findByIdForAdmin(int $transferId): ?Transfer;

    public function lockById(int $transferId): ?Transfer;

    public function findForCallback(string $reference): ?Transfer;

    /** @return Collection<int, Transfer> */
    public function dueForReconciliation(int $limit = 50): Collection;

    public function adminData(array $filters): LengthAwarePaginator;

    public function statistics(): array;

    public function sumForUserBetween(int $userId, Carbon $from, Carbon $to, array $statuses): string;

    public function create(array $attributes): Transfer;

    public function update(Transfer $transfer, array $attributes): Transfer;
}
