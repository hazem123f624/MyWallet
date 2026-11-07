<?php

namespace Modules\Wallets\Listeners;

use Modules\BankAccounts\Events\BankAccountPrimaryChanged;
use Modules\BankAccounts\Events\BankAccountUnlinked;
use Modules\BankAccounts\Events\BankAccountVerified;
use Modules\Wallets\Services\ParentWalletService;

class SyncParentWalletFundingSource
{
    public function __construct(private ParentWalletService $service) {}

    public function handle(BankAccountVerified|BankAccountPrimaryChanged|BankAccountUnlinked $event): void
    {
        $this->service->syncFundingSource($event->userId);
    }
}
