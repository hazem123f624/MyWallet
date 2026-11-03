<?php

namespace App\Services\MediaSystem\Providers;

use App\Services\MediaSystem\Models\MediaFile;
use App\Services\MediaSystem\Observers\MediaFileObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 1. Load Migrations from the package directory
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 2. Keep media_folders.size in sync with their files.
        MediaFile::observe(MediaFileObserver::class);
    }
}
