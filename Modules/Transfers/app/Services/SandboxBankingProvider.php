<?php

namespace Modules\Transfers\Services;

use Illuminate\Support\Str;
use Modules\Transfers\DTOs\BankingTransferRequestDTO;
use Modules\Transfers\DTOs\BankingTransferResultDTO;
use Modules\Transfers\Enums\TransferStatus;
use Modules\Transfers\Interfaces\BankingProviderInterface;

class SandboxBankingProvider implements BankingProviderInterface
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function execute(BankingTransferRequestDTO $request): BankingTransferResultDTO
    {
        return new BankingTransferResultDTO(
            status: TransferStatus::Successful,
            providerReference: 'SBX-'.Str::upper(Str::ulid()),
            metadata: ['provider' => 'sandbox'],
        );
    }

    public function queryStatus(string $providerReference): BankingTransferResultDTO
    {
        return new BankingTransferResultDTO(
            status: TransferStatus::Successful,
            providerReference: $providerReference,
            metadata: ['provider' => 'sandbox', 'source' => 'status_query'],
        );
    }
}
