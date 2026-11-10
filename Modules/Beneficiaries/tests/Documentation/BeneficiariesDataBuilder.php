<?php

namespace Modules\Beneficiaries\Tests\Documentation;

use App\Support\Documentation\DocumentationDataBuilder;
use Modules\Beneficiaries\Models\Beneficiary;
use Modules\Users\Models\User;

class BeneficiariesDataBuilder extends DocumentationDataBuilder
{
    public function build(array $context = []): array
    {
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
        } elseif ($user->trashed()) {
            $user->restore();
        }

        Beneficiary::withTrashed()->where('user_id', $user->id)->forceDelete();

        $accountNumber = '1122334455667788';
        $beneficiary = Beneficiary::create([
            'user_id' => $user->id,
            'name' => 'Ahmed Mohamed',
            'bank_name' => 'National Bank of Egypt',
            'bank_code' => 'NBE',
            'account_number' => $accountNumber,
            'account_number_hash' => hash('sha256', $accountNumber),
            'account_number_last4' => '7788',
            'iban' => 'EG380019000500000000263180002',
            'is_active' => true,
        ]);

        $adminAccountNumber = '2233445566778899';
        $adminBeneficiary = Beneficiary::create([
            'user_id' => $user->id,
            'name' => 'Admin Review Beneficiary',
            'bank_name' => 'Banque Misr',
            'bank_code' => 'BM',
            'account_number' => $adminAccountNumber,
            'account_number_hash' => hash('sha256', $adminAccountNumber),
            'account_number_last4' => '8899',
            'iban' => null,
            'is_active' => true,
        ]);

        return ['user' => $user, 'beneficiary' => $beneficiary, 'admin_beneficiary' => $adminBeneficiary];
    }
}
