<?php

namespace Modules\Transfers\Interfaces;

use Modules\Transfers\DTOs\BankingTransferRequestDTO;
use Modules\Transfers\DTOs\BankingTransferResultDTO;

interface BankingProviderInterface
{
    public function name(): string;

    public function execute(BankingTransferRequestDTO $request): BankingTransferResultDTO;

    public function queryStatus(string $providerReference): BankingTransferResultDTO;
}
