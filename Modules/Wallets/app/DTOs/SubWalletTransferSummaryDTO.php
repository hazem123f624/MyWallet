<?php

namespace Modules\Wallets\DTOs;

class SubWalletTransferSummaryDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $parentWalletId,
        public string $name,
        public string $availableBalance,
        public string $reservedBalance,
        public string $currency,
        public ?string $currencySymbol,
    ) {}
}
