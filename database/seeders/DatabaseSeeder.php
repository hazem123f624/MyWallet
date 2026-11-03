<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Admins\Database\Seeders\AdminsDatabaseSeeder;
use Modules\Settings\Database\Seeders\SettingsDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('security:api-key create "'.config('security.test_key').'" --no-expiry');

        $this->call([
            AdminsDatabaseSeeder::class,
            SettingsDatabaseSeeder::class,
            DocumentSeeder::class,
        ]);
    }
}
