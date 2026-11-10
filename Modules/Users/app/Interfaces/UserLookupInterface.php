<?php

namespace Modules\Users\Interfaces;

use Modules\Users\DTOs\UserSummaryDTO;

interface UserLookupInterface
{
    public function findSummary(int $userId): ?UserSummaryDTO;
}
