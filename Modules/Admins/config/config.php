<?php

use Modules\Admins\Enums\ActiveStatus;

if (! defined('BASE_ADMIN')) {
    define('BASE_ADMIN', 1);
}
if (! defined('PASSWORD')) {
    define('PASSWORD', 'P@ssw0rd');
}
if (! defined('FETCH')) {
    define('FETCH', 'fetch');
}
if (! defined('CREATE')) {
    define('CREATE', 'create');
}
if (! defined('READ')) {
    define('READ', 'read');
}
if (! defined('UPDATE')) {
    define('UPDATE', 'update');
}
if (! defined('DELETE')) {
    define('DELETE', 'delete');
}

return [
    'name' => 'Admins',

    /*
     * Base admins config (for seeding)
     */
    'base_role' => [1, 2],
    'base_admins' => [
        [
            'id' => BASE_ADMIN,
            'name' => 'Admin',
            'email' => 'admin@base.com',
            'password' => PASSWORD,
            'is_active' => App\Enums\ActiveStatus::Active,
            'phone' => '000000000000',
        ],
    ],

    /*
     * Base roles config (from Roles module)
     */
    'base_roles' => [
        'default_role' => [
            'id' => 1,
            'name' => ['ar' => 'دور تقليدى', 'en' => 'Default Role'],
            'description' => ['ar' => 'وصف الدور التقليدى', 'en' => 'Default Role Description'],
            'is_active' => App\Enums\ActiveStatus::Active,
        ],
        'super_role' => [
            'id' => 2,
            'name' => ['ar' => 'دور المشرف الرئيسي', 'en' => 'Super Admin Role '],
            'description' => ['ar' => 'وصف دور المشرف الرئيسي', 'en' => 'Super Admin Role Description'],
            'is_active' => App\Enums\ActiveStatus::Active,
        ],
    ],

    /*
     * Permissions config (from Models module)
     */
    'permissions' => [
        ['id' => 1, 'name' => FETCH, 'title' => ['en' => 'Fetch Data', 'ar' => 'إظهار البيانات']],
        ['id' => 2, 'name' => CREATE, 'title' => ['en' => 'Create Data', 'ar' => 'إضافة البيانات']],
        ['id' => 3, 'name' => READ, 'title' => ['en' => 'Read Data',  'ar' => 'إظهار التفاصيل']],
        ['id' => 4, 'name' => UPDATE, 'title' => ['en' => 'Update Data', 'ar' => 'تعديل البيانات']],
        ['id' => 5, 'name' => DELETE, 'title' => ['en' => 'Delete Data', 'ar' => 'حذف البيانات']],
    ],

    /*
     * Modules config (from Models module)
     */
    'modules' => [
        'users' => [
            'title' => ['en' => 'Users', 'ar' => 'المستخدمين'],
            'is_active' => ActiveStatus::Active,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'wallets' => [
            'title' => ['en' => 'Wallets', 'ar' => 'المحافظ'],
            'is_active' => ActiveStatus::Active,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'transactions' => [
            'title' => ['en' => 'Transactions', 'ar' => 'المعاملات'],
            'is_active' => ActiveStatus::Active,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'beneficiaries' => [
            'title' => ['en' => 'Beneficiaries', 'ar' => 'المستفيدين'],
            'is_active' => ActiveStatus::Active,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'bank_accounts' => [
            'title' => ['en' => 'Bank Accounts', 'ar' => 'الحسابات البنكية'],
            'is_active' => ActiveStatus::Active,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'reports' => [
            'title' => ['en' => 'Reports', 'ar' => 'التقارير'],
            'is_active' => ActiveStatus::Active,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
    ],
];
