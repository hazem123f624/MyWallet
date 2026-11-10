<?php

namespace Modules\Transfers\Enums;

enum TransferReversalType: string
{
    case Reversal = 'reversal';
    case Refund = 'refund';
}
