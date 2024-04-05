<?php

declare(strict_types=1);

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

    'http' => [
        /*
        |--------------------------------------------------------------------------
        | Request Logger
        |--------------------------------------------------------------------------
        */
        'request' => [
            'enabled' => env('LOG_REQUEST_ENABLED', false),
            'channel' => config('logging.default'),
            'include_paths' => [],
            'exclude_paths' => [],
            'middleware' => \LaravelBlinkLogger\Http\Middleware\RequestLogger::class,
            'middleware_group_names' => [
                'web',
                'api',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Response Logger
        |--------------------------------------------------------------------------
        */
        'response' => [
            'enabled' => env('LOG_RESPONSE_ENABLED', false),
            'channel' => config('logging.default'),
            'include_paths' => [],
            'exclude_paths' => [],
            'middleware' => \LaravelBlinkLogger\Http\Middleware\ResponseLogger::class,
            'middleware_group_names' => [
                'api',
            ],
        ],
    ],
];
