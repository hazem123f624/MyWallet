<?php

namespace Modules\Users\Interfaces;

interface TransactionPinVerifierInterface
{
    public function verify(int $userId, string $pin): void;
}
