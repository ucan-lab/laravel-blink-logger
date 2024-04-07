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
        'listeners' => [
            \Illuminate\Database\Events\QueryExecuted::class => \LaravelBlinkLogger\Listeners\QueryExecutedLogger::class,
            \Illuminate\Database\Events\TransactionBeginning::class => \LaravelBlinkLogger\Listeners\TransactionBeginningLogger::class,
            \Illuminate\Database\Events\TransactionCommitted::class => \LaravelBlinkLogger\Listeners\TransactionCommittedLogger::class,
            \Illuminate\Database\Events\TransactionRolledBack::class => \LaravelBlinkLogger\Listeners\TransactionRolledBackLogger::class,
        ],
    ],

    'http' => [
        /*
        |--------------------------------------------------------------------------
        | Request Logger
        |--------------------------------------------------------------------------
        */
        'request' => [
            'enabled' => env('LOG_HTTP_REQUEST_ENABLED', false),
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
            'enabled' => env('LOG_HTTP_RESPONSE_ENABLED', false),
            'channel' => config('logging.default'),
            'include_paths' => [],
            'exclude_paths' => [],
            'middleware' => \LaravelBlinkLogger\Http\Middleware\ResponseLogger::class,
            'middleware_group_names' => [
                'api',
            ],
        ],
    ],

    'http_client' => [
        /*
        |--------------------------------------------------------------------------
        | Request Logger
        |--------------------------------------------------------------------------
        */
        'request' => [
            'enabled' => env('LOG_HTTP_CLIENT_REQUEST_ENABLED', false),
            'channel' => config('logging.default'),
            'listeners' => [
                \Illuminate\Http\Client\Events\RequestSending::class => \LaravelBlinkLogger\Listeners\RequestSendingLogger::class,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Response Logger
        |--------------------------------------------------------------------------
        */
        'response' => [
            'enabled' => env('LOG_HTTP_CLIENT_RESPONSE_ENABLED', false),
            'channel' => config('logging.default'),
            'listeners' => [
                \Illuminate\Http\Client\Events\RequestSending::class => \LaravelBlinkLogger\Listeners\RequestSendingLogger::class,
            ],
        ],
    ],
];
