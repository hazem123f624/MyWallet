<?php

namespace Modules\BankAccounts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\BankAccounts\Models\Bank;

class BankAccountsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            [
                'code' => 'NBE',
                'name_en' => 'National Bank of Egypt',
                'name_ar' => 'البنك الأهلي المصري',
                'logo' => 'banks/nbe.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'BM',
                'name_en' => 'Banque Misr',
                'name_ar' => 'بنك مصر',
                'logo' => 'banks/banque-misr.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'CIB',
                'name_en' => 'Commercial International Bank',
                'name_ar' => 'البنك التجاري الدولي',
                'logo' => 'banks/cib.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 30,
            ],
            [
                'code' => 'ALEX',
                'name_en' => 'Alex Bank',
                'name_ar' => 'بنك الإسكندرية',
                'logo' => 'banks/alex-bank.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 40,
            ],
            [
                'code' => 'QNB',
                'name_en' => 'QNB Egypt',
                'name_ar' => 'بنك QNB مصر',
                'logo' => 'banks/qnb.png',
                'is_active' => true,
                'supports_linking' => true,
                'sort_order' => 50,
            ],
            [
                'code' => 'HSBC',
                'name_en' => 'HSBC Egypt',
                'name_ar' => 'بنك إتش إس بي سي مصر',
                'logo' => 'banks/hsbc.png',
                'is_active' => true,
                'supports_linking' => false,
                'sort_order' => 60,
            ],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(['code' => $bank['code']], $bank);
        }
    }
}
