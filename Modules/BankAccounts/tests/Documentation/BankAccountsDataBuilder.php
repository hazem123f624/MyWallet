<?php

namespace Modules\BankAccounts\Tests\Documentation;

use App\Services\OtpService;
use App\Support\Documentation\DocumentationDataBuilder;
use Illuminate\Support\Facades\Cache;
use Modules\BankAccounts\Enums\BankAccountStatus;
use Modules\BankAccounts\Models\Bank;
use Modules\BankAccounts\Models\BankAccount;
use Modules\Users\Models\User;

class BankAccountsDataBuilder extends DocumentationDataBuilder
{
    public function build(array $context = []): array
    {
        $nbe = Bank::firstOrCreate(
            ['code' => 'NBE'],
            [
                'name_en' => 'National Bank of Egypt',
                'name_ar' => 'البنك الأهلي المصري',
                'logo' => 'banks/nbe.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 10,
            ]
        );

        $banqueMisr = Bank::firstOrCreate(
            ['code' => 'BM'],
            [
                'name_en' => 'Banque Misr',
                'name_ar' => 'بنك مصر',
                'logo' => 'banks/banque-misr.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 20,
            ]
        );

        $cib = Bank::firstOrCreate(
            ['code' => 'CIB'],
            [
                'name_en' => 'Commercial International Bank',
                'name_ar' => 'البنك التجاري الدولي',
                'logo' => 'banks/cib.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 30,
            ]
        );

        $user = User::withTrashed()->where('email', 'user2@demo.com')->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Demo User',
                'email' => 'user2@demo.com',
                'phone' => '+201211172299',
                'password' => 'password',
                'is_active' => true,
                'phone_verified_at' => now(),
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }

            $user->update([
                'is_active' => true,
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ]);
        }

        BankAccount::where('user_id', $user->id)->delete();

        $verified = BankAccount::create([
            'user_id' => $user->id,
            'bank_id' => $nbe->id,
            'account_holder_name' => $user->name,
            'account_number' => '12345678901234',
            'account_number_hash' => hash('sha256', '12345678901234'),
            'account_number_last4' => '1234',
            'iban' => 'EG380019000500000000263180002',
            'is_primary' => true,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now()->subDays(10),
        ]);

        $pending = BankAccount::create([
            'user_id' => $user->id,
            'bank_id' => $banqueMisr->id,
            'account_holder_name' => $user->name,
            'account_number' => '98765432109876',
            'account_number_hash' => hash('sha256', '98765432109876'),
            'account_number_last4' => '9876',
            'iban' => null,
            'is_primary' => false,
            'status' => BankAccountStatus::Pending,
            'verified_at' => null,
        ]);

        $throwaway = BankAccount::create([
            'user_id' => $user->id,
            'bank_id' => $cib->id,
            'account_holder_name' => $user->name,
            'account_number' => '11223344556677',
            'account_number_hash' => hash('sha256', '11223344556677'),
            'account_number_last4' => '6677',
            'iban' => null,
            'is_primary' => false,
            'status' => BankAccountStatus::Verified,
            'verified_at' => now()->subDays(3),
        ]);

        $otpType = 'bank_account_verification_'.$pending->id;
        $identifier = (string) $user->phone;
        app(OtpService::class)->generateByCache($identifier, $otpType);

        $cacheKey = "otp:cache:{$otpType}:{$identifier}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $cached['last_sent_at'] = now()->subMinutes(2)->toDateTimeString();
            Cache::put($cacheKey, $cached, now()->addMinutes(5));
        }

        return [
            'user' => $user,
            'verified' => $verified,
            'pending' => $pending,
            'throwaway' => $throwaway,
            'banks' => ['nbe' => $nbe, 'banque_misr' => $banqueMisr, 'cib' => $cib],
        ];
    }
}
