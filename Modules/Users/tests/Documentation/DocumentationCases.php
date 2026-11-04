<?php

namespace Modules\Users\Tests\Documentation;

use App\Classes\TestCases\InitTestCaseBuilder;
use App\Classes\TestCases\TestCaseBuilder;
use App\Enums\ActiveStatus;
use App\Http\Controllers\ApiDocumentationController;
use Modules\Users\Models\User;

class DocumentationCases
{
    /**
     * Define the documentation test cases for this module.
     * Use the TestCaseBuilder for a fluent definition.
     */
    public static function cases(): array
    {
        $fakeLogin = fakeLogin();
        $testCasesObject = new InitTestCaseBuilder;

        // Document Users management endpoints
        $managementCases = self::managementModule('users', $testCasesObject, $fakeLogin);

        // Document Mobile Auth endpoints
        $mobileCases = self::mobileModule('users', $testCasesObject, $fakeLogin);

        return [
            'index' => $managementCases['users-index'] ?? [],
            'show' => $managementCases['users-show'] ?? [],
            'recycle-bin' => $managementCases['users-recycle-bin'] ?? [],
            'mobile-auth' => $mobileCases['mobile-auth'] ?? [],
            'mobile-profile' => $mobileCases['mobile-profile'] ?? [],
            'mobile-transaction-pin' => $mobileCases['mobile-transaction-pin'] ?? [],
        ];
    }

    private static function managementModule($model, $testCasesObject, $fakeLogin)
    {
        // 1. Ensure an active user exists for index/show cases
        $latestRecord = User::latest()->first();
        if (! $latestRecord) {
            $latestRecord = User::withTrashed()->where('phone', '0501112223')->first();
            if ($latestRecord) {
                $latestRecord->restore();
            } else {
                $latestRecord = User::create([
                    'name' => 'Test User',
                    'phone' => '0501112223',
                    'email' => 'test_doc@example.com',
                    'password' => 'P@ssw0rd123',
                    'is_active' => ActiveStatus::Active,
                ]);
            }
        }
        $targetId = $latestRecord->id;

        // 2. Ensure a trashed user exists for recycle-bin cases
        $trashedRecord = User::onlyTrashed()->latest()->first();
        if (! $trashedRecord) {
            $trashedRecord = User::create([
                'name' => 'Trashed User',
                'phone' => '0509998887',
                'email' => 'trashed_doc@example.com',
                'password' => 'P@ssw0rd123',
                'is_active' => ActiveStatus::Active,
            ]);
            $trashedRecord->delete();
        }
        $trashedTargetId = $trashedRecord->id;

        $descriptions = app(ApiDocumentationController::class)->returnDescription($model);

        $index = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['index']['name'], route: config('api.prefixes.dashboard').'/'.$model, params: [], auth: $fakeLogin, desc: $descriptions['index']['description'], shortDesc: $descriptions['index']['short_description']),
        ];

        $show = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['show']['name'], route: config('api.prefixes.dashboard').'/'.$model.'/'.$targetId, params: [], auth: $fakeLogin, desc: $descriptions['show']['description'], shortDesc: $descriptions['show']['short_description']),
            $testCasesObject->handle(type: 'PATCH', name: $descriptions['active']['name'], route: config('api.prefixes.dashboard').'/'.$model.'/'.$targetId.'/toggle-active', params: [], auth: $fakeLogin, desc: $descriptions['active']['description'], shortDesc: $descriptions['active']['short_description']),
            $testCasesObject->handle(type: 'DELETE', name: $descriptions['delete']['name'], route: config('api.prefixes.dashboard').'/'.$model.'/'.$targetId, params: [], auth: $fakeLogin, desc: $descriptions['delete']['description'], shortDesc: $descriptions['delete']['short_description']),
        ];

        $recycleBin = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['trash']['name'], route: config('api.prefixes.dashboard').'/'.$model.'/trash', params: [], auth: $fakeLogin, desc: $descriptions['trash']['description'], shortDesc: $descriptions['trash']['short_description']),
            $testCasesObject->handle(type: 'PATCH', name: $descriptions['restore']['name'], route: config('api.prefixes.dashboard').'/'.$model.'/'.$trashedTargetId.'/restore', params: [], auth: $fakeLogin, desc: $descriptions['restore']['description'], shortDesc: $descriptions['restore']['short_description']),
            $testCasesObject->handle(type: 'DELETE', name: $descriptions['force_delete']['name'], route: config('api.prefixes.dashboard').'/'.$model.'/'.$trashedTargetId.'/force-delete', params: [], auth: $fakeLogin, desc: $descriptions['force_delete']['description'], shortDesc: $descriptions['force_delete']['short_description']),
        ];

        return [
            $model.'-index' => $index,
            $model.'-show' => $show,
            $model.'-recycle-bin' => $recycleBin,
        ];
    }

    private static function mobileModule($model, $testCasesObject, $fakeLogin)
    {
        $descriptions = app(ApiDocumentationController::class)->returnDescription($model);
        $prefix = 'mobile/'.$model;

        $auth = [
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_register']['name'], route: $prefix.'/register', params: ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '0501234567', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123'], auth: false, desc: $descriptions['mobile_register']['description'], shortDesc: $descriptions['mobile_register']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_verify']['name'], route: $prefix.'/verify', params: ['phone' => '0501234567', 'otp' => '1234'], auth: false, desc: $descriptions['mobile_verify']['description'], shortDesc: $descriptions['mobile_verify']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_login']['name'], route: $prefix.'/login', params: ['phone' => '0501234567', 'password' => 'P@ssw0rd123'], auth: false, desc: $descriptions['mobile_login']['description'], shortDesc: $descriptions['mobile_login']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_resend_otp']['name'], route: $prefix.'/resend-otp', params: ['phone' => '0501234567'], auth: false, desc: $descriptions['mobile_resend_otp']['description'], shortDesc: $descriptions['mobile_resend_otp']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_forget_password']['name'], route: $prefix.'/forget-password', params: ['phone' => '0501234567'], auth: false, desc: $descriptions['mobile_forget_password']['description'], shortDesc: $descriptions['mobile_forget_password']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_reset_password']['name'], route: $prefix.'/reset-password', params: ['phone' => '0501234567', 'otp' => '1234', 'password' => 'NewP@ssw0rd123', 'password_confirmation' => 'NewP@ssw0rd123'], auth: false, desc: $descriptions['mobile_reset_password']['description'], shortDesc: $descriptions['mobile_reset_password']['short_description']),
        ];

        $profile = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['mobile_profile']['name'], route: $prefix.'/profile', params: [], auth: $fakeLogin, desc: $descriptions['mobile_profile']['description'], shortDesc: $descriptions['mobile_profile']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_update_profile']['name'], route: $prefix.'/profile', params: ['name' => 'John Updated', 'email' => 'john_up@example.com', 'postal_code' => '12345', 'main_address' => '123 Main St, Anytown, USA', 'delete_avatar' => 0,], auth: $fakeLogin, desc: $descriptions['mobile_update_profile']['description'], shortDesc: $descriptions['mobile_update_profile']['short_description'],),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_change_password']['name'], route: $prefix.'/change-password', params: ['old_password' => 'P@ssw0rd123', 'new_password' => 'NewP@ssw0rd123', 'new_password_confirmation' => 'NewP@ssw0rd123'], auth: $fakeLogin, desc: $descriptions['mobile_change_password']['description'], shortDesc: $descriptions['mobile_change_password']['short_description']),
            $testCasesObject->handle(type: 'DELETE', name: $descriptions['mobile_delete_account']['name'], route: $prefix.'/profile', params: [], auth: fakeUserLogin(), desc: $descriptions['mobile_delete_account']['description'], shortDesc: $descriptions['mobile_delete_account']['short_description']),
        ];

        $transactionPinAuth = fakeUserLogin();
        User::where('email', 'user2@demo.com')->update([
            'transaction_pin' => null,
            'transaction_pin_failed_attempts' => 0,
            'transaction_pin_locked_until' => null,
        ]);

        $transactionPin = [
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_set_transaction_pin']['name'], route: $prefix.'/transaction-pin', params: ['transaction_pin' => '2468', 'transaction_pin_confirmation' => '2468'], auth: $transactionPinAuth, desc: $descriptions['mobile_set_transaction_pin']['description'], shortDesc: $descriptions['mobile_set_transaction_pin']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_change_transaction_pin']['name'], route: $prefix.'/transaction-pin/change', params: ['current_transaction_pin' => '2468', 'transaction_pin' => '1357', 'transaction_pin_confirmation' => '1357'], auth: $transactionPinAuth, desc: $descriptions['mobile_change_transaction_pin']['description'], shortDesc: $descriptions['mobile_change_transaction_pin']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_forget_transaction_pin']['name'], route: $prefix.'/transaction-pin/forget', params: [], auth: $transactionPinAuth, desc: $descriptions['mobile_forget_transaction_pin']['description'], shortDesc: $descriptions['mobile_forget_transaction_pin']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_reset_transaction_pin']['name'], route: $prefix.'/transaction-pin/reset', params: ['otp' => '1234', 'transaction_pin' => '2468', 'transaction_pin_confirmation' => '2468'], auth: $transactionPinAuth, desc: $descriptions['mobile_reset_transaction_pin']['description'], shortDesc: $descriptions['mobile_reset_transaction_pin']['short_description']),
            $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_verify_transaction_pin']['name'], route: $prefix.'/transaction-pin/verify', params: ['transaction_pin' => '2468'], auth: $transactionPinAuth, desc: $descriptions['mobile_verify_transaction_pin']['description'], shortDesc: $descriptions['mobile_verify_transaction_pin']['short_description']),
        ];

        return [
            'mobile-auth' => $auth,
            'mobile-profile' => $profile,
            'mobile-transaction-pin' => $transactionPin,
        ];
    }
}
