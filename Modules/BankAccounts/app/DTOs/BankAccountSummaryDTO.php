<?php

namespace Modules\BankAccounts\DTOs;

use Illuminate\Support\Carbon;

class BankAccountSummaryDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $bankId,
        public string $bankCode,
        public string $bankName,
        public string $maskedAccountNumber,
        public bool $isPrimary,
        public string $status,
        public ?Carbon $verifiedAt,
    ) {}
}
