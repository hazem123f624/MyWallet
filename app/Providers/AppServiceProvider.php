<?php

namespace App\Providers;

use App\Services\Documentations\Interfaces\DocumentationsInterface;
use App\Services\Documentations\Repositories\DocumentationsRepository;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DocumentationsInterface::class,
            DocumentationsRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

      //  URL::forceScheme('https');
        $this->registerDynamicGuards();
    }

    /**
     * Dynamically register auth guards and providers from config/auth_guards.php
     */
    private function registerDynamicGuards(): void
    {
        $authGuards = config('auth_guards.types', []);

        foreach ($authGuards as $key => $config) {
            // Register Provider
            config([
                "auth.providers.{$key}" => [
                    'driver' => 'eloquent',
                    'model'  => $config['model'],
                ],
            ]);

            // Register Guard
            config([
                "auth.guards.{$key}" => [
                    'driver'   => 'session', // Use session to support attempt() method
                    'provider' => $key,
                ],
            ]);
        }
    }
}
