<?php

namespace Modules\Wallets\Tests\Documentation;

use App\Support\Documentation\DocumentationDataBuilder;
use Modules\BankAccounts\Tests\Documentation\BankAccountsDataBuilder;
use Modules\Wallets\Models\SubWallet;
use Modules\Wallets\Models\WalletOperation;
use Modules\Wallets\Services\ParentWalletService;
use Modules\Wallets\Services\SubWalletService;

class WalletsDataBuilder extends DocumentationDataBuilder
{
    public function build(array $context = []): array
    {
        $bankScenario = BankAccountsDataBuilder::run();
        $user = $bankScenario['user'];
        $bankAccount = $bankScenario['verified'];

        $walletService = app(ParentWalletService::class);
        $walletService->ensureForUser($user->id, $user->country_id ? (int) $user->country_id : null);
        $wallet = $walletService->syncFundingSource($user->id);

        WalletOperation::where('parent_wallet_id', $wallet->id)->delete();
        SubWallet::withTrashed()->where('parent_wallet_id', $wallet->id)->forceDelete();

        $wallet->update([
            'total_allocated_balance' => 20000,
            'available_balance' => 20000,
            'reserved_balance' => 0,
        ]);

        $subWalletService = app(SubWalletService::class);

        $electricity = $subWalletService->create($user->id, [
            'name' => 'Electricity',
            'color' => '#d97706',
            'icon' => 'bolt',
        ]);
        $rent = $subWalletService->create($user->id, [
            'name' => 'Rent',
            'color' => '#2563eb',
            'icon' => 'home',
        ]);
        $food = $subWalletService->create($user->id, [
            'name' => 'Food',
            'color' => '#059669',
            'icon' => 'utensils',
        ]);

        $subWalletService->allocate($user->id, $electricity->id, 3000);
        $subWalletService->allocate($user->id, $rent->id, 7000);
        $subWalletService->allocate($user->id, $food->id, 5000);

        $throwaway = $subWalletService->create($user->id, [
            'name' => 'Temporary',
            'color' => '#475569',
            'icon' => 'wallet',
        ]);
        $subWalletService->allocate($user->id, $throwaway->id, 500);

        return [
            'user' => $user,
            'wallet' => $wallet->fresh(),
            'bank_account' => $bankAccount,
            'sub_wallets' => [
                'electricity' => $electricity->fresh(),
                'rent' => $rent->fresh(),
                'food' => $food->fresh(),
                'throwaway' => $throwaway->fresh(),
            ],
        ];
    }
}
