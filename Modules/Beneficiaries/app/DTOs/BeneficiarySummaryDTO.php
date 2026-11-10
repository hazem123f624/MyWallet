<?php

namespace Modules\Beneficiaries\DTOs;

class BeneficiarySummaryDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public string $bankName,
        public ?string $bankCode,
        public string $maskedAccountNumber,
        public ?string $maskedIban,
        public bool $isActive,
    ) {}
}
