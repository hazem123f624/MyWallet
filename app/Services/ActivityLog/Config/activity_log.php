<?php

return [
    /*
     * The table name to use for storing activity logs.
     */
    'table_name' => 'activity_logs',

    /*
     * The model class to use for activity logs.
     */
    'activity_model' => \App\Services\ActivityLog\Models\ActivityLog::class,

    /*
     * The default causer to use if none is provided.
     * Can be a class name or null.
     */
    'default_causer' => \Modules\Admins\Models\Admins::class,

    /*
     * Attributes that should be ignored when logging changes.
     * These will not appear in the 'properties' diff when a model is updated.
     */
    'ignored_attributes' => [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'deleted_at',
        'otp_pin',
        'otp_token',
        'transaction_pin',
    ],
];
