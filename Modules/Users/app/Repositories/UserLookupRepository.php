<?php

namespace Modules\Users\Repositories;

use Modules\Users\DTOs\UserSummaryDTO;
use Modules\Users\Interfaces\UserLookupInterface;
use Modules\Users\Models\User;

class UserLookupRepository implements UserLookupInterface
{
    public function findSummary(int $userId): ?UserSummaryDTO
    {
        $user = User::query()->find($userId);

        return $user ? new UserSummaryDTO(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            phone: $user->phone,
        ) : null;
    }
}
