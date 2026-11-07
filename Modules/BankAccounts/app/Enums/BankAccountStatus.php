<?php

namespace Modules\BankAccounts\Enums;

enum BankAccountStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
}
