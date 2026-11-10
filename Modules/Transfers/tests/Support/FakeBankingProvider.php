<?php

namespace Modules\Transfers\Tests\Support;

use Modules\Transfers\DTOs\BankingTransferRequestDTO;
use Modules\Transfers\DTOs\BankingTransferResultDTO;
use Modules\Transfers\Interfaces\BankingProviderInterface;

class FakeBankingProvider implements BankingProviderInterface
{
    public function __construct(private BankingTransferResultDTO $result) {}

    public function name(): string
    {
        return 'fake';
    }

    public function execute(BankingTransferRequestDTO $request): BankingTransferResultDTO
    {
        return $this->result;
    }

    public function queryStatus(string $providerReference): BankingTransferResultDTO
    {
        return $this->result;
    }
}
