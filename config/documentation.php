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
    'consumers' => [
        'User App' => ['mobile', 'user'],
        'Dashboard' => ['dashboard'],
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
