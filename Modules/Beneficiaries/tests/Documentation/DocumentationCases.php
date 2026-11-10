<?php

namespace Modules\Beneficiaries\Tests\Documentation;

use App\Classes\TestCases\InitTestCaseBuilder;
use Modules\Beneficiaries\Http\Requests\StoreBeneficiaryRequest;
use Modules\Beneficiaries\Http\Requests\UpdateBeneficiaryRequest;

class DocumentationCases
{
    public static function cases(): array
    {
        $token = fakeUserLogin();
        $scenario = BeneficiariesDataBuilder::run();
        $adminToken = fakeLogin();
        $beneficiary = $scenario['beneficiary'];
        $adminBeneficiary = $scenario['admin_beneficiary'];
        $testCasesObject = new InitTestCaseBuilder;

        $mobile = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Beneficiaries',
                route: 'mobile/beneficiaries',
                params: [],
                auth: $token,
                desc: 'Returns the authenticated user\'s saved transfer beneficiaries with masked account details.',
                shortDesc: 'List saved beneficiaries.'
            ),
            $testCasesObject->handle(
                type: 'POST',
                name: 'Create Beneficiary',
                route: 'mobile/beneficiaries',
                params: [
                    'name' => 'Sara Ali',
                    'bank_name' => 'Banque Misr',
                    'bank_code' => 'BM',
                    'account_number' => '5566778899001122',
                    'iban' => 'EG380019000500000000263180003',
                ],
                auth: $token,
                desc: 'Creates a beneficiary for the authenticated user. Account numbers are stored securely and masked in responses.',
                shortDesc: 'Add a beneficiary.',
                notes: StoreBeneficiaryRequest::class
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Beneficiary',
                route: 'mobile/beneficiaries/'.$beneficiary->id,
                params: [],
                auth: $token,
                desc: 'Returns one saved beneficiary owned by the authenticated user.',
                shortDesc: 'View a beneficiary.'
            ),
            $testCasesObject->handle(
                type: 'PATCH',
                name: 'Update Beneficiary',
                route: 'mobile/beneficiaries/'.$beneficiary->id,
                params: ['name' => 'Ahmed Updated', 'bank_name' => 'National Bank of Egypt'],
                auth: $token,
                desc: 'Updates the beneficiary name, bank, account, or active status.',
                shortDesc: 'Edit a beneficiary.',
                notes: UpdateBeneficiaryRequest::class
            ),
            $testCasesObject->handle(
                type: 'DELETE',
                name: 'Delete Beneficiary',
                route: 'mobile/beneficiaries/'.$beneficiary->id,
                params: [],
                auth: $token,
                desc: 'Soft deletes a saved beneficiary.',
                shortDesc: 'Delete a beneficiary.'
            ),
        ];

        $admin = [
            $testCasesObject->handle(
                type: 'GET',
                name: 'List Beneficiaries (Admin)',
                route: 'dashboard/beneficiaries',
                params: [],
                auth: $adminToken,
                desc: 'Returns all saved beneficiaries with their owning users for administrator review.',
                shortDesc: 'Admin beneficiary list.'
            ),
            $testCasesObject->handle(
                type: 'GET',
                name: 'Get Beneficiary (Admin)',
                route: 'dashboard/beneficiaries/'.$adminBeneficiary->id,
                params: [],
                auth: $adminToken,
                desc: 'Returns beneficiary details and the owning user for administrator review.',
                shortDesc: 'Admin beneficiary details.'
            ),
        ];

        return [
            'mobile-beneficiaries' => $mobile,
            'dashboard-beneficiaries' => $admin,
        ];
    }
}
