<?php

namespace Modules\Wallets\Enums;

enum ParentWalletStatus: string
{
    case Unlinked = 'unlinked';
    case Active = 'active';
    case Suspended = 'suspended';
}
