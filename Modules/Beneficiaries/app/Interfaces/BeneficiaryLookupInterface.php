<?php

namespace Modules\Beneficiaries\Interfaces;

use Modules\Beneficiaries\DTOs\BeneficiarySummaryDTO;

interface BeneficiaryLookupInterface
{
    public function findSummaryForUser(int $userId, int $beneficiaryId): ?BeneficiarySummaryDTO;
}
