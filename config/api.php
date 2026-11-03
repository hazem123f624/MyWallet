<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version Prefix
    |--------------------------------------------------------------------------
    |
    | Base version prefix applied to module API routes (e.g. "v1"). Keep route
    | files referencing config('api.version') instead of hard-coding the string,
    | so the whole API can be re-versioned from one place.
    |
    */
    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Named Segment Prefixes
    |--------------------------------------------------------------------------
    |
    | Reusable path segments composed after the version prefix, e.g.
    | config('api.version').'/'.config('api.prefixes.mobile') => "v1/mobile".
    |
    */
    'prefixes' => [
        'mobile' => env('API_MOBILE_PREFIX', 'mobile'),
    ],

];
