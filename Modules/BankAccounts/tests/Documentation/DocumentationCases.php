<?php

namespace Modules\BankAccounts\Tests\Documentation;

use App\Classes\TestCases\InitTestCaseBuilder;
use Modules\BankAccounts\Http\Requests\StoreBankAccountRequest;
use Modules\BankAccounts\Http\Requests\VerifyBankAccountRequest;

class DocumentationCases
{
    public static function cases(): array
    {
        $token = fakeUserLogin();
        $scenario = BankAccountsDataBuilder::run();
        $testCasesObject = new InitTestCaseBuilder;

        $banks = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Supported Banks',
                route: 'mobile/banks',
                params: [],
                auth: $token,
                desc: 'Returns the banks that currently allow MyWallet account linking. Banks without an available integration are excluded.',
                shortDesc: 'View supported banks.'
            ),
        ];

        $accounts = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Linked Bank Accounts',
                route: 'mobile/bank-accounts',
                params: [],
                auth: $token,
                desc: 'Returns every bank account linked by the authenticated user with its ownership-verification status and primary designation.',
                shortDesc: 'View linked bank accounts.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Link Bank Account',
                route: 'mobile/bank-accounts',
                params: [
                    'bank_id' => $scenario['banks']['cib']->id,
                    'account_holder_name' => $scenario['user']->name,
                    'account_number' => '5555666677778888',
                    'iban' => 'EG380019000500000000263180003',
                ],
                auth: $token,
                desc: 'Links the user\'s own bank account as a funding source and sends an ownership-verification OTP. The account stays unusable until it is verified.',
                shortDesc: 'Link a bank account.',
                notes: StoreBankAccountRequest::class
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Resend Bank Account Verification OTP',
                route: 'mobile/bank-accounts/'.$scenario['pending']->id.'/resend-otp',
                params: [],
                auth: $token,
                desc: 'Sends a fresh ownership-verification OTP for a pending bank account after the resend cooldown.',
                shortDesc: 'Resend verification code.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Verify Bank Account Ownership',
                route: 'mobile/bank-accounts/'.$scenario['pending']->id.'/verify',
                params: ['otp' => '1234'],
                auth: $token,
                desc: 'Confirms ownership of a linked bank account with the OTP sent to the registered phone. A verified account becomes usable as a funding source.',
                shortDesc: 'Verify bank account ownership.',
                notes: VerifyBankAccountRequest::class
            ),
            $testCasesObject->handle(
                type: 'PATCH',
                name: 'Set Primary Bank Account',
                route: 'mobile/bank-accounts/'.$scenario['verified']->id.'/primary',
                params: [],
                auth: $token,
                desc: 'Marks a verified bank account as the single primary funding source and clears the previous primary designation.',
                shortDesc: 'Choose the primary account.'
            ),
            $testCasesObject->handle(
                type: 'DELETE',
                name: 'Unlink Bank Account',
                route: 'mobile/bank-accounts/'.$scenario['throwaway']->id,
                params: [],
                auth: $token,
                desc: 'Removes a linked bank account so it can no longer be used as a funding source.',
                shortDesc: 'Unlink a bank account.'
            ),
        ];

        return [
            'mobile-bank-accounts' => array_merge($banks, $accounts),
        ];
    }
}
