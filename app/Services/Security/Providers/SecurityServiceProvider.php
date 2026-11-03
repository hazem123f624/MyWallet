<?php

namespace App\Services\Security\Providers;

use App\Services\Security\Console\ApiKeyCommand;
use App\Services\Security\Middleware\NonceMiddleware;
use App\Services\Security\Middleware\ThreatDetectionMiddleware;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            config_path('security.php'), 'security'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ApiKeyCommand::class,
            ]);
        }
    }
}
