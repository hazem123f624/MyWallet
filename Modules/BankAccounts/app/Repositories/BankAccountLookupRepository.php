<?php

namespace Modules\BankAccounts\Repositories;

use Modules\BankAccounts\DTOs\BankAccountSummaryDTO;
use Modules\BankAccounts\Interfaces\BankAccountLookupInterface;
use Modules\BankAccounts\Models\BankAccount;

class BankAccountLookupRepository implements BankAccountLookupInterface
{
    public function findForUser(int $userId, int $bankAccountId): ?BankAccountSummaryDTO
    {
        $account = BankAccount::query()
            ->ownedBy($userId)
            ->with('bank')
            ->find($bankAccountId);

        return $account ? $this->toSummary($account) : null;
    }

    public function findById(int $bankAccountId): ?BankAccountSummaryDTO
    {
        $account = BankAccount::query()
            ->with('bank')
            ->find($bankAccountId);

        return $account ? $this->toSummary($account) : null;
    }

    public function findPrimaryVerifiedForUser(int $userId): ?BankAccountSummaryDTO
    {
        $account = BankAccount::query()
            ->ownedBy($userId)
            ->verified()
            ->where('is_primary', true)
            ->with('bank')
            ->first();

        return $account ? $this->toSummary($account) : null;
    }

    private function toSummary(BankAccount $account): BankAccountSummaryDTO
    {
        $bank = $account->bank;
        $bankName = app()->getLocale() === 'ar'
            ? (string) ($bank->name_ar ?: $bank->name_en)
            : (string) ($bank->name_en ?: $bank->name_ar);

        return new BankAccountSummaryDTO(
            id: $account->id,
            userId: $account->user_id,
            bankId: $account->bank_id,
            bankCode: (string) $bank->code,
            bankName: $bankName,
            maskedAccountNumber: '****'.$account->account_number_last4,
            isPrimary: (bool) $account->is_primary,
            status: $account->status->value,
            verifiedAt: $account->verified_at,
        );
    }
}
