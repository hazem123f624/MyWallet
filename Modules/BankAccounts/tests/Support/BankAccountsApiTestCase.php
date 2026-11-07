<?php

namespace Modules\BankAccounts\Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Models\Bank;
use Modules\BankAccounts\Models\BankAccount;
use Modules\Users\Models\User;
use Tests\TestCase;

abstract class BankAccountsApiTestCase extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableSecurityMiddleware();
        Cache::flush();
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function disableSecurityMiddleware(): void
    {
        config([
            'security.enabled' => false,
            'security.threat_detection.enabled' => false,
            'security.ip_filter.enabled' => false,
            'security.rate_limit.enabled' => false,
            'security.force_json.enabled' => false,
            'security.payload_size.enabled' => false,
            'security.security_headers.enabled' => false,
            'security.cors.enabled' => false,
            'security.api_key.enabled' => false,
            'security.timestamp.enabled' => false,
            'security.signature.enabled' => false,
            'security.nonce.enabled' => false,
            'security.logging.enabled' => false,
        ]);
    }

    protected function createUser(array $overrides = []): User
    {
        $sequence = ++self::$sequence;

        return User::create(array_merge([
            'name' => 'Bank Test User '.$sequence,
            'email' => 'bank-user-'.$sequence.'@example.com',
            'phone' => '+2010'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'password' => 'P@ssw0rd123',
            'is_active' => true,
            'phone_verified_at' => now(),
        ], $overrides));
    }

    protected function createBank(array $overrides = []): Bank
    {
        $sequence = ++self::$sequence;

        return Bank::create(array_merge([
            'code' => 'BANK'.$sequence,
            'name_en' => 'Test Bank '.$sequence,
            'name_ar' => 'بنك اختبار '.$sequence,
            'logo' => null,
            'is_active' => true,
            'supports_linking' => true,
            'sort_order' => $sequence,
        ], $overrides));
    }

    protected function createBankAccount(User $user, Bank $bank, array $overrides = []): BankAccount
    {
        $sequence = ++self::$sequence;
        $accountNumber = '12345678'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT);

        return BankAccount::create(array_merge([
            'user_id' => $user->id,
            'bank_id' => $bank->id,
            'account_holder_name' => $user->name,
            'account_number' => $accountNumber,
            'account_number_hash' => hash('sha256', $accountNumber),
            'account_number_last4' => substr($accountNumber, -4),
            'iban' => null,
            'is_primary' => false,
            'status' => BankAccountStatus::Pending,
            'verified_at' => null,
        ], $overrides));
    }

    protected function tokenFor(User $user, array $abilities = ['users']): string
    {
        return $user->createToken('bank-accounts-test', $abilities)->plainTextToken;
    }

    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
