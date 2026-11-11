<?php

namespace Modules\Transfers\Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Admins\Models\Admins;
use Modules\Beneficiaries\Models\Beneficiary;
use Modules\Users\Models\User;
use Modules\Wallets\Enums\ParentWalletStatus;
use Modules\Wallets\Models\ParentWallet;
use Modules\Wallets\Models\SubWallet;
use Tests\TestCase;

abstract class TransfersApiTestCase extends TestCase
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
            'name' => 'Transfer User '.$sequence,
            'email' => 'transfer-user-'.$sequence.'@example.com',
            'phone' => '+2010'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'password' => 'P@ssw0rd123',
            'transaction_pin' => '2468',
            'is_active' => true,
            'phone_verified_at' => now(),
        ], $overrides));
    }

    protected function createWallet(User $user, mixed $available = 20000): ParentWallet
    {
        return ParentWallet::create([
            'user_id' => $user->id,
            'bank_account_id' => null,
            'currency' => 'EGP',
            'currency_symbol' => 'E£',
            'total_allocated_balance' => $available,
            'available_balance' => 0,
            'reserved_balance' => 0,
            'status' => ParentWalletStatus::Active,
            'linked_at' => now(),
        ]);
    }

    protected function createSubWallet(ParentWallet $wallet, mixed $available = 1000, mixed $reserved = 0): SubWallet
    {
        return SubWallet::create([
            'parent_wallet_id' => $wallet->id,
            'name' => 'Spending',
            'color' => '#7c3aed',
            'icon' => 'wallet',
            'available_balance' => $available,
            'reserved_balance' => $reserved,
        ]);
    }

    protected function createBeneficiary(User $user, array $overrides = []): Beneficiary
    {
        $sequence = ++self::$sequence;
        $accountNumber = '12345678'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT);

        return Beneficiary::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Beneficiary '.$sequence,
            'bank_name' => 'National Bank of Egypt',
            'bank_code' => 'NBE',
            'account_number' => $accountNumber,
            'account_number_hash' => hash('sha256', $accountNumber),
            'account_number_last4' => substr($accountNumber, -4),
            'iban' => null,
            'is_active' => true,
        ], $overrides));
    }

    protected function createAdmin(): Admins
    {
        $sequence = ++self::$sequence;

        return Admins::create([
            'name' => 'Transfer Admin '.$sequence,
            'email' => 'transfer-admin-'.$sequence.'@example.com',
            'phone' => '+2010'.str_pad((string) (900000 + $sequence), 8, '0', STR_PAD_LEFT),
            'password' => 'P@ssw0rd123',
            'is_active' => true,
        ]);
    }

    protected function tokenFor(User $user, array $abilities = ['users']): string
    {
        return $user->createToken('transfers-test', $abilities)->plainTextToken;
    }

    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
