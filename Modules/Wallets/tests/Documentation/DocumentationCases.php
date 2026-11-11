<?php

namespace Modules\Wallets\Tests\Documentation;

use App\Classes\TestCases\InitTestCaseBuilder;
use Modules\Wallets\Http\Requests\AllocateSubWalletRequest;
use Modules\Wallets\Http\Requests\RedistributeSubWalletRequest;
use Modules\Wallets\Http\Requests\StoreSubWalletRequest;
use Modules\Wallets\Http\Requests\UpdateFundingSourceRequest;
use Modules\Wallets\Http\Requests\UpdateSubWalletRequest;

class DocumentationCases
{
    public static function cases(): array
    {
        $token = fakeUserLogin();
        $scenario = WalletsDataBuilder::run();
        $testCasesObject = new InitTestCaseBuilder;

        $parentWalletCases = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Parent Wallet',
                route: 'mobile/parent-wallet',
                params: [],
                auth: $token,
                desc: 'Returns the authenticated user\'s Parent Wallet, including its status, currency, total allocated balance, available balance, reserved balance, Sub Wallet summary, recent internal operations, and current funding source. A missing wallet is created automatically for legacy users.',
                shortDesc: 'View the Parent Wallet.'
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Private Wallet Operations',
                route: 'mobile/parent-wallet/operations',
                params: [
                    'type' => 'allocated',
                    'per_page' => 10,
                ],
                auth: $token,
                desc: 'Returns a paginated history of internal wallet operations for the authenticated user. Every entry is marked private and never appears in the external transfer history.',
                shortDesc: 'List private wallet activity.'
            ),
            $testCasesObject->handle(
                type: 'PUT',
                name: 'Link Parent Wallet Funding Source',
                route: 'mobile/parent-wallet/funding-source',
                params: ['bank_account_id' => $scenario['bank_account']->id],
                auth: $token,
                desc: 'Links the Parent Wallet to the user\'s verified primary bank account. Only a verified primary account owned by the authenticated user can fund the wallet.',
                shortDesc: 'Link the wallet funding source.',
                notes: UpdateFundingSourceRequest::class
            ),
        ];

        $subWalletCases = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Sub Wallets',
                route: 'mobile/sub-wallets',
                params: [],
                auth: $token,
                desc: 'Returns every active Sub Wallet owned by the authenticated user with its colors, icon, available balance, and reserved balance.',
                shortDesc: 'List my Sub Wallets.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Create Sub Wallet',
                route: 'mobile/sub-wallets',
                params: [
                    'name' => 'Savings',
                    'color' => '#0891b2',
                    'icon' => 'piggy-bank',
                ],
                auth: $token,
                desc: 'Creates a Sub Wallet under the user\'s Parent Wallet. The wallet starts with zero balances. A user can have at most 12 active Sub Wallets.',
                shortDesc: 'Create a Sub Wallet.',
                notes: StoreSubWalletRequest::class
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Sub Wallet',
                route: 'mobile/sub-wallets/'.$scenario['sub_wallets']['electricity']->id,
                params: [],
                auth: $token,
                desc: 'Returns one Sub Wallet owned by the authenticated user, including its current available and reserved balances.',
                shortDesc: 'View a Sub Wallet.'
            ),
            $testCasesObject->handle(
                type: 'PATCH',
                name: 'Update Sub Wallet',
                route: 'mobile/sub-wallets/'.$scenario['sub_wallets']['electricity']->id,
                params: [
                    'name' => 'Utilities',
                    'color' => '#d97706',
                    'icon' => 'bolt',
                ],
                auth: $token,
                desc: 'Renames a Sub Wallet or changes its color and icon. Balances are never changed by this endpoint.',
                shortDesc: 'Rename or restyle a Sub Wallet.',
                notes: UpdateSubWalletRequest::class
            ),
            $testCasesObject->handle(
                type: 'DELETE',
                name: 'Delete Sub Wallet',
                route: 'mobile/sub-wallets/'.$scenario['sub_wallets']['throwaway']->id,
                params: [],
                auth: $token,
                desc: 'Deletes a Sub Wallet and returns its available balance to the Parent Wallet. A Sub Wallet with reserved funds cannot be deleted.',
                shortDesc: 'Delete a Sub Wallet.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Allocate Funds to Sub Wallet',
                route: 'mobile/sub-wallets/'.$scenario['sub_wallets']['electricity']->id.'/allocate',
                params: ['amount' => 1000],
                auth: $token,
                desc: 'Moves funds from the Parent Wallet available balance into a Sub Wallet and records an internal wallet operation.',
                shortDesc: 'Allocate funds to a Sub Wallet.',
                notes: AllocateSubWalletRequest::class
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Redistribute Sub Wallet Funds',
                route: 'mobile/sub-wallets/'.$scenario['sub_wallets']['rent']->id.'/redistribute',
                params: [
                    'destination_sub_wallet_id' => $scenario['sub_wallets']['food']->id,
                    'amount' => 500,
                ],
                auth: $token,
                desc: 'Moves available funds between two Sub Wallets owned by the authenticated user and records an internal wallet operation. The Parent Wallet balance is unchanged.',
                shortDesc: 'Redistribute between Sub Wallets.',
                notes: RedistributeSubWalletRequest::class
            ),
        ];

        return [
            'mobile-parent-wallet' => $parentWalletCases,
            'mobile-sub-wallets' => $subWalletCases,
        ];
    }
}
