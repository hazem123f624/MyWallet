<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\MiddlewareAliasServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    App\Services\ActivityLog\ActivityLogServiceProvider::class,
    App\Services\MediaSystem\Providers\MediaServiceProvider::class,
    App\Services\Security\Providers\SecurityServiceProvider::class,
];
