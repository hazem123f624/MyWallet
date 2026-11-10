<?php

namespace Modules\Users\Services;

use Modules\Users\Interfaces\TransactionPinVerifierInterface;
use Modules\Users\Models\User;

class TransactionPinVerifier implements TransactionPinVerifierInterface
{
    public function __construct(private TransactionPinService $service) {}

    public function verify(int $userId, string $pin): void
    {
        $user = User::query()->findOrFail($userId);
        $this->service->verify($user, $pin);
    }
}
