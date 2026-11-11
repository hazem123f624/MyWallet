<?php

namespace Modules\Transfers\DTOs;

use Modules\Transfers\Enums\TransferStatus;

class BankingTransferResultDTO
{
    public function __construct(
        public TransferStatus $status,
        public ?string $providerReference = null,
        public ?string $failureReason = null,
        public array $metadata = [],
    ) {}
}
