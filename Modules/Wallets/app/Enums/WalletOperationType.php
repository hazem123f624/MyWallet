<?php

namespace Modules\Wallets\Enums;

enum WalletOperationType: string
{
    case Allocated = 'allocated';
    case Redistributed = 'redistributed';
    case SubWalletReturned = 'sub_wallet_returned';
}
