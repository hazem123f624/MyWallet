<?php

namespace Modules\Transfers\DTOs;

class BankingTransferRequestDTO
{
    public function __construct(
        public string $reference,
        public string $amount,
        public string $currency,
        public string $beneficiaryName,
        public string $beneficiaryBankName,
        public string $beneficiaryAccountLast4,
    ) {}
}
