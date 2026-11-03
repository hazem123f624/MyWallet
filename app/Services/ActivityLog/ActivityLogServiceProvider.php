<?php

namespace App\Services\ActivityLog;

use Illuminate\Support\ServiceProvider;

class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/Config/activity_log.php', 'activity_log'
        );

        // Bind the service
        $this->app->singleton(ActivityLogService::class, function ($app) {
            return new ActivityLogService();
        });
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        // Load migrations from the service directory
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Publish configuration if needed
        $this->publishes([
            __DIR__.'/Config/activity_log.php' => config_path('activity_log.php'),
        ], 'activity-log-config');
    }
}
