<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Settings;

class SettingsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billingSettings = [
            [
                'key' => 'currency',
                'name_en' => 'Currency',
                'name_ar' => 'العملة',
                'data' => ['value' => 'SAR'],
            ],
            [
                'key' => 'payment_grace_period',
                'name_en' => 'Payment Grace Period',
                'name_ar' => 'فترة سماح الدفع',
                'data' => ['value' => 7],
            ],
            [
                'key' => 'payment_post_expiration_period',
                'name_en' => 'Payment Post Expiration Period',
                'name_ar' => 'فترة ما بعد انتهاء الدفع',
                'data' => ['value' => 30],
            ],
            [
                'key' => 'vat',
                'name_en' => 'VAT',
                'name_ar' => 'ضريبة القيمة المضافة',
                'data' => ['value' => 14],
            ],
        ];

        foreach ($billingSettings as $setting) {
            Settings::updateOrCreate(
                [
                    'type' => 'billing',
                    'key' => $setting['key'],
                ],
                [
                    'name_en' => $setting['name_en'],
                    'name_ar' => $setting['name_ar'],
                    'data' => $setting['data'],
                    'is_active' => true,
                ]
            );
        }
    }
}
