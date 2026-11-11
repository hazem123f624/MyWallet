<?php

namespace Modules\BankAccounts\Interfaces;

use Modules\BankAccounts\DTOs\BankAccountSummaryDTO;

interface BankAccountLookupInterface
{
    public function findForUser(int $userId, int $bankAccountId): ?BankAccountSummaryDTO;

    public function findById(int $bankAccountId): ?BankAccountSummaryDTO;

    public function findPrimaryVerifiedForUser(int $userId): ?BankAccountSummaryDTO;
}
