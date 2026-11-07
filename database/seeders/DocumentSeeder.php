<?php

namespace Database\Seeders;

use App\Models\Documentations;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class DocumentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $documentations = [
            ['name' => 'Error 401 – Unauthorized', 'section' => '2', 'category' => 'get-status-code-401', 'sorting' => '3', 'short_description' => 'The request requires authentication. The user is either not logged in or does not have a valid access token.', 'description' => null, 'headers' => null, 'query_params' => null, 'body' => null, 'method' => null, 'route' => null, 'success_response' => null, 'error_response' => '{"message": "Unauthorized", "success": false}', 'developer_tested' => '1', 'tester_tested' => '1', 'is_active' => '1', 'created_at' => '2026-02-06 14:39:55', 'updated_at' => '2026-02-06 14:39:55'],
            ['name' => 'Error 403 – Forbidden', 'section' => '2', 'category' => 'get-status-code-403', 'sorting' => '4', 'short_description' => 'The request was understood, but the server refuses to authorize it. The user does not have permission to perform this action.', 'description' => null, 'headers' => null, 'query_params' => null, 'body' => null, 'method' => null, 'route' => null, 'success_response' => null, 'error_response' => '{"message": "You are not allowed to perform this action", "success": false}', 'developer_tested' => '1', 'tester_tested' => '1', 'is_active' => '1', 'created_at' => '2026-02-06 14:39:55', 'updated_at' => '2026-02-06 14:39:55'],
            ['name' => 'Error 404 – Not Found', 'section' => '2', 'category' => 'get-status-code-404', 'sorting' => '5', 'short_description' => 'The requested resource could not be found on the server. It may have been moved or deleted.', 'description' => null, 'headers' => null, 'query_params' => null, 'body' => null, 'method' => null, 'route' => null, 'success_response' => null, 'error_response' => '{"message": "Resource not found", "success": false}', 'developer_tested' => '1', 'tester_tested' => '1', 'is_active' => '1', 'created_at' => '2026-02-06 14:39:55', 'updated_at' => '2026-02-06 14:39:55'],
            ['name' => 'Error 405 – Method Not Allowed', 'section' => '2', 'category' => 'get-status-code-405', 'sorting' => '6', 'short_description' => 'The HTTP method used is not supported for this endpoint. Check the allowed request methods.', 'description' => null, 'headers' => null, 'query_params' => null, 'body' => null, 'method' => null, 'route' => null, 'success_response' => null, 'error_response' => '{"message": "Method not allowed", "success": false}', 'developer_tested' => '1', 'tester_tested' => '1', 'is_active' => '1', 'created_at' => '2026-02-06 14:39:55', 'updated_at' => '2026-02-06 14:39:55'],
            ['name' => 'Error 419 – Page Expired', 'section' => '2', 'category' => 'get-status-code-419', 'sorting' => '7', 'short_description' => 'The session or CSRF token has expired. Please refresh the page and try again.', 'description' => null, 'headers' => null, 'query_params' => null, 'body' => null, 'method' => null, 'route' => null, 'success_response' => null, 'error_response' => '{"message": "Page Expired", "success": false}', 'developer_tested' => '1', 'tester_tested' => '1', 'is_active' => '1', 'created_at' => '2026-02-06 14:39:55', 'updated_at' => '2026-02-06 14:39:55'],
            ['name' => 'Error 422 – Unprocessable Entity', 'section' => '2', 'category' => 'get-status-code-422', 'sorting' => '8', 'short_description' => 'The server understood the request, but the submitted data was invalid. Please review and correct the errors.(Errors may be return array or string)', 'description' => null, 'headers' => null, 'query_params' => null, 'body' => null, 'method' => null, 'route' => null, 'success_response' => null, 'error_response' => '{"errors": [], "message": "Validation Error", "success": false}', 'developer_tested' => '1', 'tester_tested' => '1', 'is_active' => '1', 'created_at' => '2026-02-06 14:39:55', 'updated_at' => '2026-02-06 14:39:55'],
            ['name' => 'Error 500 – Internal Server Error', 'section' => '2', 'category' => 'get-status-code-500', 'sorting' => '9', 'short_description' => 'An unexpected error occurred on the server. Please try again later or contact support.', 'description' => null, 'headers' => null, 'query_params' => null, 'body' => null, 'method' => null, 'route' => null, 'success_response' => null, 'error_response' => '{"message": "Server Error", "success": false}', 'developer_tested' => '1', 'tester_tested' => '1', 'is_active' => '1', 'created_at' => '2026-02-06 14:39:55', 'updated_at' => '2026-02-06 14:39:55'],
        ];

        Documentations::whereIn('category', array_column($documentations, 'category'))->delete();
        Documentations::insert($documentations);

        $this->call([AuthSeeder::class]);

        $this->flushRateLimit();
        $this->command?->info('Running test case on Users Module: mobile-transaction-pin');
        Artisan::call('app:init-test Users mobile-transaction-pin');

        $createIndex = [
            'Admins' => ['authentication', 'index'],
            'Users' => ['index'],
        ];

        $showUpdateDelete = [
            'Admins' => ['show'],
            'Users' => ['show'],
        ];

        $recycleBin = [
            'Admins' => ['recycle-bin'],
            'Users' => ['recycle-bin'],
        ];

        foreach ([$createIndex, $showUpdateDelete, $recycleBin] as $phase) {
            foreach ($phase as $module => $commands) {
                foreach ($commands as $command) {
                    $this->flushRateLimit();
                    $this->command?->info('Running test case on '.$module.' Module: '.$command);
                    Artisan::call('app:init-test '.$module.' '.$command);
                }
            }
        }

        $this->flushRateLimit();
        $this->command?->info('Running test case on BankAccounts Module: mobile-bank-accounts');
        Artisan::call('app:init-test BankAccounts mobile-bank-accounts');

        $this->flushRateLimit();
        $this->command?->info('Running test case on Wallets Module: mobile-parent-wallet');
        Artisan::call('app:init-test Wallets mobile-parent-wallet');

        $this->flushRateLimit();
        $this->command?->info('Running test case on Wallets Module: mobile-sub-wallets');
        Artisan::call('app:init-test Wallets mobile-sub-wallets');
    }

    /**
     * Flush the custom rate-limit cache entries for the loopback IP so that
     * the seeder's internal HTTP requests are never throttled.
     */
    private function flushRateLimit(): void
    {
        $ip = request()->ip() ?? '127.0.0.1';
        $cacheKey = "security:rate_limit:{$ip}";

        Cache::forget($cacheKey);
        Cache::forget("{$cacheKey}:ttl");
    }
}
