<?php

return [
    'name' => 'Transfers',
    'provider' => 'sandbox',
    'min_amount' => 1,
    'max_amount' => 100000,
    'daily_limit' => 50000,
    'monthly_limit' => 200000,
    'callback_secret' => env('TRANSFERS_CALLBACK_SECRET'),
    'reconcile_interval_minutes' => 5,
    'max_check_attempts' => 20,
    'max_backoff_minutes' => 60,
];
