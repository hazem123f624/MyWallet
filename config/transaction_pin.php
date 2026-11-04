<?php

return [
    'max_attempts' => (int) env('TRANSACTION_PIN_MAX_ATTEMPTS', 5),
    'lock_minutes' => (int) env('TRANSACTION_PIN_LOCK_MINUTES', 15),
];
