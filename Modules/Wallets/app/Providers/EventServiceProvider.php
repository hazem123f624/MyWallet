<?php

namespace Modules\Wallets\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\BankAccounts\Events\BankAccountPrimaryChanged;
use Modules\BankAccounts\Events\BankAccountUnlinked;
use Modules\BankAccounts\Events\BankAccountVerified;
use Modules\Users\Events\UserAccountVerified;
use Modules\Wallets\Listeners\CreateParentWallet;
use Modules\Wallets\Listeners\SyncParentWalletFundingSource;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserAccountVerified::class => [
            CreateParentWallet::class,
        ],
        BankAccountVerified::class => [
            SyncParentWalletFundingSource::class,
        ],
        BankAccountPrimaryChanged::class => [
            SyncParentWalletFundingSource::class,
        ],
        BankAccountUnlinked::class => [
            SyncParentWalletFundingSource::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
