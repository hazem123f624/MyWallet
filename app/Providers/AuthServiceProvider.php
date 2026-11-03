<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Admins\Models\Admins;
use Modules\Admins\Policies\AdminsPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings.
     */
    protected $policies = [
//        Admins::class => AdminsPolicy::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
