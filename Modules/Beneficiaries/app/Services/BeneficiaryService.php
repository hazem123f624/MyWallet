<?php

namespace Modules\Beneficiaries\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Beneficiaries\Interfaces\BeneficiaryInterface;
use Modules\Beneficiaries\Models\Beneficiary;

class BeneficiaryService
{
    public function __construct(private BeneficiaryInterface $repository) {}

    public function list(int $userId): Collection
    {
        return $this->repository->listForUser($userId);
    }

    public function view(int $userId, int $beneficiaryId): Beneficiary
    {
        return $this->findOwnedOrFail($userId, $beneficiaryId);
    }

    public function create(int $userId, array $data): Beneficiary
    {
        [$accountNumber, $hash, $last4] = $this->accountData($data['account_number']);

        return DB::transaction(function () use ($userId, $data, $accountNumber, $hash, $last4): Beneficiary {
            $existing = $this->repository->findByOwnerHash($userId, $hash);

            if ($existing && ! $existing->trashed()) {
                throw ValidationException::withMessages([
                    'account_number' => __('beneficiaries::messages.already_exists'),
                ]);
            }

            $attributes = [
                'name' => $data['name'],
                'bank_name' => $data['bank_name'],
                'bank_code' => $data['bank_code'] ?? null,
                'account_number' => $accountNumber,
                'account_number_hash' => $hash,
                'account_number_last4' => $last4,
                'iban' => $data['iban'] ?? null,
                'is_active' => true,
            ];

            if ($existing) {
                $existing->restore();

                return $this->repository->update($existing, $attributes);
            }

            return $this->repository->create($attributes + ['user_id' => $userId]);
        });
    }

    public function update(int $userId, int $beneficiaryId, array $data): Beneficiary
    {
        return DB::transaction(function () use ($userId, $beneficiaryId, $data): Beneficiary {
            $beneficiary = $this->findOwnedOrFail($userId, $beneficiaryId, true);
            $attributes = array_intersect_key($data, array_flip(['name', 'bank_name', 'bank_code', 'iban', 'is_active']));

            if (! empty($data['account_number'])) {
                [$accountNumber, $hash, $last4] = $this->accountData($data['account_number']);
                $existing = $this->repository->findByOwnerHash($userId, $hash);

                if ($existing && $existing->id !== $beneficiary->id) {
                    throw ValidationException::withMessages([
                        'account_number' => __('beneficiaries::messages.already_exists'),
                    ]);
                }

                $attributes['account_number'] = $accountNumber;
                $attributes['account_number_hash'] = $hash;
                $attributes['account_number_last4'] = $last4;
            }

            return $this->repository->update($beneficiary, $attributes);
        });
    }

    public function delete(int $userId, int $beneficiaryId): void
    {
        $beneficiary = $this->findOwnedOrFail($userId, $beneficiaryId);
        $this->repository->delete($beneficiary);
    }

    public function adminList(array $filters)
    {
        return $this->repository->adminData($filters);
    }

    public function adminView(int $beneficiaryId): Beneficiary
    {
        $beneficiary = Beneficiary::query()->find($beneficiaryId);

        if (! $beneficiary) {
            throw (new ModelNotFoundException)->setModel(Beneficiary::class, [$beneficiaryId]);
        }

        return $beneficiary;
    }

    private function findOwnedOrFail(int $userId, int $beneficiaryId, bool $lock = false): Beneficiary
    {
        $beneficiary = $this->repository->findForUser($userId, $beneficiaryId, $lock);

        if (! $beneficiary) {
            throw (new ModelNotFoundException)->setModel(Beneficiary::class, [$beneficiaryId]);
        }

        return $beneficiary;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function accountData(string $accountNumber): array
    {
        $normalized = preg_replace('/\D+/', '', $accountNumber) ?? '';

        return [$normalized, hash('sha256', $normalized), substr($normalized, -4)];
    }
}
