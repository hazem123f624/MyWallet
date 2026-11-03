<?php

declare(strict_types=1);

return [

    'preset' => 'laravel',

    // المسار المقيد — يضمن فحص ملفات الموديول فقط
    'paths' => [
        base_path('Modules/Roles'),
    ],

    'exclude' => [
        base_path('app'),
        base_path('bootstrap'),
        base_path('config'),
        base_path('database'),
        base_path('public'),
        base_path('resources'),
        base_path('routes'),
        base_path('storage'),
        base_path('tests'),
        base_path('vendor'),
    ],

    'add' => [],

    'remove' => [],

    'requirements' => [],
];
