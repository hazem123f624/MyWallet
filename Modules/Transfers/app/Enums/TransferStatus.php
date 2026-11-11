<?php

namespace Modules\Transfers\Enums;

enum TransferStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Successful = 'successful';
    case Failed = 'failed';
    case Reversed = 'reversed';
    case Refunded = 'refunded';
}
