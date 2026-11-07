<?php

namespace Modules\Wallets\Listeners;

use Modules\Users\Events\UserAccountVerified;
use Modules\Wallets\Services\ParentWalletService;

class CreateParentWallet
{
    public function __construct(private ParentWalletService $service) {}

    public function handle(UserAccountVerified $event): void
    {
        $this->service->ensureForUser($event->userId, $event->countryId);
    }
}
