<?php

namespace Modules\Transfers\Tests\Documentation;

use App\Classes\TestCases\InitTestCaseBuilder;
use Modules\Transfers\Http\Requests\StoreTransferRequest;
use Modules\Transfers\Services\TransferService;

class DocumentationCases
{
    public static function cases(): array
    {
        $token = fakeUserLogin();
        $scenario = TransfersDataBuilder::run();
        $adminToken = fakeLogin();
        $testCasesObject = new InitTestCaseBuilder;

        $existing = app(TransferService::class)->create($scenario['user']->id, [
            'sub_wallet_id' => $scenario['sub_wallet']->id,
            'beneficiary_id' => $scenario['beneficiary']->id,
            'amount' => 150,
            'transaction_pin' => '2468',
            'client_reference' => 'docs-existing-transfer',
        ]);

        $mobile = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Transfer Limits',
                route: 'mobile/transfers/limits',
                params: [],
                auth: $token,
                desc: 'Returns the configured minimum, maximum, daily, and monthly transfer limits with the remaining allowance.',
                shortDesc: 'View transfer limits.'
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Recent Transfers',
                route: 'mobile/transfers',
                params: [],
                auth: $token,
                desc: 'Returns the authenticated user\'s latest ten transfer requests and their current statuses.',
                shortDesc: 'List recent transfers.'
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Transfer History',
                route: 'mobile/transfers/history',
                params: [
                    'status' => 'successful',
                    'reference' => $existing->reference,
                    'per_page' => 10,
                ],
                auth: $token,
                desc: 'Returns a paginated, searchable history of the authenticated user\'s external transfers. Filter by reference, status, and date range. Internal wallet operations are never included.',
                shortDesc: 'Search transfer history.'
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Transfer',
                route: 'mobile/transfers/'.$existing->id,
                params: [],
                auth: $token,
                desc: 'Returns one transfer owned by the authenticated user with beneficiary, source Sub Wallet, provider reference, status, lifecycle events, and reversal or refund details when present.',
                shortDesc: 'View a transfer.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Refresh Transfer Status',
                route: 'mobile/transfers/'.$existing->id.'/refresh',
                params: [],
                auth: $token,
                desc: 'Queries the Banking Provider for a pending or processing transfer and applies the latest status. Terminal transfers are returned unchanged. This endpoint is rate limited.',
                shortDesc: 'Refresh transfer status.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Create Transfer',
                route: 'mobile/transfers',
                params: [
                    'sub_wallet_id' => $scenario['sub_wallet']->id,
                    'beneficiary_id' => $scenario['beneficiary']->id,
                    'amount' => 250,
                    'transaction_pin' => '2468',
                    'client_reference' => 'docs-new-transfer',
                ],
                auth: $token,
                desc: 'Validates the Sub Wallet balance, transfer limits, beneficiary ownership, and transaction PIN, reserves the amount, and executes the transfer through the configured Banking Provider.',
                shortDesc: 'Send money.',
                notes: StoreTransferRequest::class
            ),
        ];

        $admin = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Transfers (Admin)',
                route: 'dashboard/transfers',
                params: [],
                auth: $adminToken,
                desc: 'Returns all transfer requests with user, beneficiary, source wallet, amount, and status.',
                shortDesc: 'Admin transfer list.'
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Transfer Statistics (Admin)',
                route: 'dashboard/transfers/statistics',
                params: [],
                auth: $adminToken,
                desc: 'Returns aggregate transfer counts, successful volume, and reversed/refunded totals for the dashboard.',
                shortDesc: 'Admin transfer statistics.'
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Transfer Details (Admin)',
                route: 'dashboard/transfers/'.$existing->id,
                params: [],
                auth: $adminToken,
                desc: 'Returns one transfer with user, beneficiary, provider reference, status, lifecycle events, and reversal or refund details for administrator review.',
                shortDesc: 'Admin transfer details.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Sync Transfer Status (Admin)',
                route: 'dashboard/transfers/'.$existing->id.'/sync',
                params: [],
                auth: $adminToken,
                desc: 'Queries the Banking Provider for a pending or processing transfer and applies the latest status. This is a status sync only; administrators cannot issue manual refunds.',
                shortDesc: 'Admin sync transfer status.'
            ),
        ];

        return [
            'mobile-transfers' => $mobile,
            'dashboard-transfers' => $admin,
        ];
    }
}
