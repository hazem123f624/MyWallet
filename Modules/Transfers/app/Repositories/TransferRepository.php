<?php

namespace Modules\Transfers\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Modules\Transfers\Interfaces\TransferInterface;
use Modules\Transfers\Models\Transfer;
use Modules\Transfers\Support\Money;

class TransferRepository implements TransferInterface
{
    public function __construct(private Transfer $model) {}

    public function listForUser(int $userId): Collection
    {
        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->latest()
            ->limit(10)
            ->get();
    }

    public function historyForUser(int $userId, array $filters): LengthAwarePaginator
    {
        $reference = trim((string) ($filters['reference'] ?? ''));

        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($reference !== '', fn ($query) => $query->where('reference', 'like', '%'.$reference.'%'))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(50, max(1, (int) ($filters['per_page'] ?? 15))));
    }

    public function findForUser(int $userId, int $transferId): ?Transfer
    {
        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->find($transferId);
    }

    public function findByClientReference(int $userId, string $clientReference): ?Transfer
    {
        return $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->where('client_reference', $clientReference)
            ->first();
    }

    public function findByIdForAdmin(int $transferId): ?Transfer
    {
        return $this->model->newQuery()->find($transferId);
    }

    public function lockById(int $transferId): ?Transfer
    {
        return $this->model
            ->newQuery()
            ->whereKey($transferId)
            ->lockForUpdate()
            ->first();
    }

    public function findForCallback(string $reference): ?Transfer
    {
        return $this->model
            ->newQuery()
            ->where(function ($query) use ($reference): void {
                $query->where('reference', $reference)
                    ->orWhere('provider_reference', $reference);
            })
            ->first();
    }

    public function dueForReconciliation(int $limit = 50): Collection
    {
        return $this->model
            ->newQuery()
            ->whereIn('status', ['pending', 'processing'])
            ->where(function ($query): void {
                $query->whereNull('next_check_at')
                    ->orWhere('next_check_at', '<=', now());
            })
            ->orderBy('next_check_at')
            ->limit($limit)
            ->get();
    }

    public function adminData(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->model
            ->newQuery()
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', (int) $filters['user_id']))
            ->when(! empty($filters['reference']), fn ($query) => $query->where('reference', 'like', '%'.$filters['reference'].'%'))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhere('beneficiary_name', 'like', "%{$search}%")
                        ->orWhere('beneficiary_bank_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function statistics(): array
    {
        $successful = $this->model->newQuery()->where('status', 'successful')->sum('amount');

        $reversed = $this->model->newQuery()->whereIn('status', ['reversed', 'refunded'])->sum('amount');

        return [
            'total' => $this->model->newQuery()->count(),
            'pending' => $this->model->newQuery()->whereIn('status', ['pending', 'processing'])->count(),
            'successful' => $this->model->newQuery()->where('status', 'successful')->count(),
            'failed' => $this->model->newQuery()->where('status', 'failed')->count(),
            'reversed' => $this->model->newQuery()->where('status', 'reversed')->count(),
            'refunded' => $this->model->newQuery()->where('status', 'refunded')->count(),
            'volume' => Money::normalize($successful),
            'refunded_volume' => Money::normalize($reversed),
        ];
    }

    public function sumForUserBetween(int $userId, Carbon $from, Carbon $to, array $statuses): string
    {
        $sum = $this->model
            ->newQuery()
            ->ownedBy($userId)
            ->whereBetween('requested_at', [$from, $to])
            ->whereIn('status', $statuses)
            ->sum('amount');

        return Money::normalize($sum);
    }

    public function create(array $attributes): Transfer
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Transfer $transfer, array $attributes): Transfer
    {
        $transfer->fill($attributes)->save();

        return $transfer;
    }
}
