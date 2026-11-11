<?php

namespace Modules\Wallets\Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Models\Bank;
use Modules\BankAccounts\Models\BankAccount;
use Modules\Settings\Enums\SettingsType;
use Modules\Settings\Models\Settings;
use Modules\Users\Models\User;
use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Models\ParentWallet;
use Modules\Wallets\Models\SubWallet;
use Tests\TestCase;

abstract class WalletsApiTestCase extends TestCase
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

    protected function createCountry(string $currency = 'EGP', string $symbol = 'E£'): Settings
    {
        $sequence = ++self::$sequence;

        $country = Settings::create([
            'type' => SettingsType::COUNTRY->value,
            'name_ar' => 'مصر '.$sequence,
            'name_en' => 'Egypt '.$sequence,
            'is_active' => true,
            'data' => [
                'code' => 'EG',
                'phone_code' => '20',
                'phone_length' => 11,
                'currency' => $currency,
                'currency_symbol' => $symbol,
            ],
        ]);

        Cache::flush();

        return $country;
    }

    protected function createUser(array $overrides = []): User
    {
        $sequence = ++self::$sequence;

        return User::create(array_merge([
            'name' => 'Wallet User '.$sequence,
            'email' => 'wallet-user-'.$sequence.'@example.com',
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
            'code' => 'WBANK'.$sequence,
            'name_en' => 'Wallet Bank '.$sequence,
            'name_ar' => 'بنك المحفظة '.$sequence,
            'is_active' => true,
            'supports_linking' => true,
            'sort_order' => $sequence,
        ], $overrides));
    }

    protected function createBankAccount(User $user, Bank $bank, array $overrides = []): BankAccount
    {
        $sequence = ++self::$sequence;
        $accountNumber = '99887766'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT);

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

    protected function createWallet(User $user, array $overrides = []): ParentWallet
    {
        return ParentWallet::create(array_merge([
            'user_id' => $user->id,
            'bank_account_id' => null,
            'currency' => 'EGP',
            'currency_symbol' => 'E£',
            'total_allocated_balance' => 0,
            'available_balance' => 0,
            'reserved_balance' => 0,
            'status' => ParentWalletStatus::Unlinked,
            'linked_at' => null,
        ], $overrides));
    }

    protected function createSubWallet(ParentWallet $wallet, array $overrides = []): SubWallet
    {
        return SubWallet::create(array_merge([
            'parent_wallet_id' => $wallet->id,
            'name' => 'Sub Wallet',
            'color' => '#7c3aed',
            'icon' => 'wallet',
            'available_balance' => 0,
            'reserved_balance' => 0,
        ], $overrides));
    }

    protected function tokenFor(User $user, array $abilities = ['users']): string
    {
        return $user->createToken('wallets-test', $abilities)->plainTextToken;
    }

    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
