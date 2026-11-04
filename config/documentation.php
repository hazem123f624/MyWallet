<?php

return [
    'name' => 'Documentations',

    /*
    |--------------------------------------------------------------------------
    | API consumer sections (routing-derived)
    |--------------------------------------------------------------------------
    | Endpoints are grouped by consumer based on the longest matching route
    | prefix below. New modules are placed automatically from their URL.
    */
    'default_consumer' => 'Dashboard',

    /*
    |--------------------------------------------------------------------------
    | API grouping
    |--------------------------------------------------------------------------
    | Modules with more than this many endpoints are split into feature
    | subgroups in the documentation navigation and content.
    */
    'group_threshold' => (int) env('API_DOCS_GROUP_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Display order
    |--------------------------------------------------------------------------
    | Keep common modules and feature groups in a predictable reading order.
    | Unknown items fall to the end.
    */
    'module_order' => [
        'User App' => ['Authentication', 'Account'],
        'Dashboard' => ['Authentication', 'Administration', 'Users', 'Account', 'Activity Logs'],
    ],

    'group_order' => [
        'Registration & Verification' => 10,
        'Login & Session' => 20,
        'Password Reset' => 30,
        'Profile' => 40,
        'Password' => 50,
        'Transaction PIN' => 60,
        'User Management' => 70,
        'Recycle Bin' => 80,
        'Session & Security' => 90,
        'Admins' => 110,
        'Admin Recycle Bin' => 120,
        'Roles' => 130,
        'Role Assignments' => 140,
        'Role Recycle Bin' => 150,
        'Models' => 160,
        'Counts' => 170,
        'General' => 999,
    ],
    'consumers' => [
        'User App' => array_values(array_unique(array_filter([env('API_MOBILE_PREFIX', 'mobile'), 'user']))),
        'Dashboard' => array_values(array_unique(array_filter([env('API_DASHBOARD_PREFIX', 'dashboard'), 'dashboard']))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Classification overrides
    |--------------------------------------------------------------------------
    | Optional explicit mappings for endpoints whose route prefix does not
    | reflect their real consumer.
    */
    'overrides' => [],

    /*
    |--------------------------------------------------------------------------
    | Module display labels
    |--------------------------------------------------------------------------
    | Optional labels for the module grouping within each section. Resources
    | not listed here fall back to a Title-Cased version of their slug.
    */
    'modules' => [
        'users' => 'Users',
        'users.register' => 'Authentication',
        'users.login' => 'Authentication',
        'users.verify' => 'Authentication',
        'users.forget-password' => 'Authentication',
        'users.verify-forget-password' => 'Authentication',
        'users.reset-password' => 'Authentication',
        'users.resend-otp' => 'Authentication',
        'users.refresh-token' => 'Authentication',
        'users.logout' => 'Authentication',
        'users.profile' => 'Account',
        'users.change-password' => 'Account',
        'users.transaction-pin' => 'Account',
        'users.transaction-pin.change' => 'Account',
        'users.transaction-pin.forget' => 'Account',
        'users.transaction-pin.reset' => 'Account',
        'users.transaction-pin.verify' => 'Account',
        'profile' => 'Account',
        'change-password' => 'Account',
        'admins' => 'Administration',
        'roles' => 'Administration',
        'models' => 'Administration',
        'counts' => 'Administration',
        'activity-logs' => 'Activity Logs',
        'login' => 'Authentication',
        'logout' => 'Authentication',
        'forget-password' => 'Authentication',
        'reset-password' => 'Authentication',
        'verify-forget-password-otp' => 'Authentication',
        'verify-otp' => 'Authentication',
        'resend-otp' => 'Authentication',
        'resend-forgot-password-otp' => 'Authentication',
        'refresh-token' => 'Authentication',
    ],
];
