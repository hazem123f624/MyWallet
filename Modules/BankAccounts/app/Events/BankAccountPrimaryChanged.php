<?php

namespace Modules\BankAccounts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankAccountPrimaryChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public int $bankAccountId,
    ) {}
}
