<?php

/*
|--------------------------------------------------------------------------
| Blink Logger
|--------------------------------------------------------------------------
*/
return [
    /*
    |--------------------------------------------------------------------------
    | Query Logger
    |--------------------------------------------------------------------------
    */

    'query' => [
        'enabled' => env('LOG_QUERY_ENABLED', false),
        'channel' => config('logging.default'),
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
