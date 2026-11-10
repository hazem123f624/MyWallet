<?php

namespace Modules\Transfers\Tests\Documentation;

use App\Support\Documentation\DocumentationDataBuilder;
use Modules\Beneficiaries\Models\Beneficiary;
use Modules\Transfers\Models\Transfer;
use Modules\Users\Models\User;
use Modules\Wallets\Tests\Documentation\WalletsDataBuilder;

class TransfersDataBuilder extends DocumentationDataBuilder
{
    public function build(array $context = []): array
    {
        $existingUser = User::withTrashed()->where('email', 'user2@demo.com')->first();

        if ($existingUser) {
            Transfer::where('user_id', $existingUser->id)->delete();
        }

        $walletScenario = WalletsDataBuilder::run();
        $user = $walletScenario['user'];
        $subWallet = $walletScenario['sub_wallets']['food'];

        $user->update(['transaction_pin' => '2468']);
        $user->refresh();

        Transfer::where('user_id', $user->id)->delete();
        Beneficiary::where('user_id', $user->id)->where('name', 'Transfer Demo Beneficiary')->forceDelete();

        $accountNumber = '9988776655443322';
        $beneficiary = Beneficiary::create([
            'user_id' => $user->id,
            'name' => 'Transfer Demo Beneficiary',
            'bank_name' => 'Commercial International Bank',
            'bank_code' => 'CIB',
            'account_number' => $accountNumber,
            'account_number_hash' => hash('sha256', $accountNumber),
            'account_number_last4' => '3322',
            'iban' => null,
            'is_active' => true,
        ]);

        return [
            'user' => $user,
            'sub_wallet' => $subWallet,
            'beneficiary' => $beneficiary,
        ];
    }
}
