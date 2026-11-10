<?php

namespace Modules\Wallets\Interfaces;

use Modules\Wallets\DTOs\SubWalletTransferSummaryDTO;

interface TransferWalletInterface
{
    public function findSubWalletForUser(int $userId, int $subWalletId): ?SubWalletTransferSummaryDTO;

    public function reserveForTransfer(int $userId, int $subWalletId, string $amount): SubWalletTransferSummaryDTO;

    public function settleReservation(int $userId, int $subWalletId, string $amount): void;

    public function releaseReservation(int $userId, int $subWalletId, string $amount): void;

    public function creditReversal(int $userId, int $subWalletId, string $amount): void;
}
