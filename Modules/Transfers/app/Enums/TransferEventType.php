<?php

namespace Modules\Transfers\Enums;

enum TransferEventType: string
{
    case Created = 'created';
    case CallbackReceived = 'callback_received';
    case StatusChecked = 'status_checked';
    case Processing = 'processing';
    case Successful = 'successful';
    case Failed = 'failed';
    case Reversed = 'reversed';
    case Refunded = 'refunded';
}
