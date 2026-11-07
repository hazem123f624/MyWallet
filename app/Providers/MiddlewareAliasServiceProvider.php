<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Modules\Users\Http\Middleware\EnsureUserIsActive;

class MiddlewareAliasServiceProvider extends ServiceProvider
{
    /**
     * Register middleware aliases early so they're available for module routes.
     */
    public function register(): void
    {
        // Register aliases directly with the router
        $this->app['router']->aliasMiddleware('abilities', CheckAbilities::class);
        $this->app['router']->aliasMiddleware('ability', CheckForAnyAbility::class);
        $this->app['router']->aliasMiddleware('user.active', EnsureUserIsActive::class);
    }
}
