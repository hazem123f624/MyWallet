<?php
return [

    'single_factor_auth' => true,
    'test' => [
        'token' => 'SJXwJKTiW6hxnFSn7i7IyScUUYpIQSCM9o5L4qmZBgcHlaNBzQKpe05QPKNrbPIw',
        'otp' => '1234',
    ],
    'types' => [
        'admins' => [
            'model'    => \Modules\Admins\Models\Admins::class,
            'guard'    => 'admins',
            'dtoClass' => \Modules\Admins\DTOs\AdminsDTO::class,
        ],
        'users' => [
            'model'    => \Modules\Users\Models\User::class,
            'guard'    => 'users',
            'dtoClass' => \Modules\Users\DTOs\UsersDTO::class,
        ],
    ],

];
