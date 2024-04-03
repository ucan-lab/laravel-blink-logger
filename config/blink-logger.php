<?php

/*
|--------------------------------------------------------------------------
| Blink Logger
|--------------------------------------------------------------------------
*/
return [
    /*
    |--------------------------------------------------------------------------
    | SQL Logger
    |--------------------------------------------------------------------------
    */

    'sql' => [
        'enabled' => env('LOG_SQL_ENABLED', false),
        'slow_query_time' => env('LOG_SQL_SLOW_QUERY_TIME', 2000), // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Logger
    |--------------------------------------------------------------------------
    */
    'request' => [
        'enabled' => env('LOG_REQUEST_ENABLED', false),
        'channel' => config('logging.default'),
        'exclude' => [
            '_debugbar',
        ],
        'middleware' => \LaravelBlinkLogger\Http\Middleware\RequestLogger::class,
        'middleware_group_names' => [
            'web',
            'api',
        ],
    ],
];
