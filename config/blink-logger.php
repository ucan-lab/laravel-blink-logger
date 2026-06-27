<?php

declare(strict_types=1);
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use LaravelBlinkLogger\Http\Middleware\RequestLogger;
use LaravelBlinkLogger\Http\Middleware\ResponseLogger;
use LaravelBlinkLogger\Listeners\QueryExecutedLogger;
use LaravelBlinkLogger\Listeners\RequestSendingLogger;
use LaravelBlinkLogger\Listeners\ResponseReceivedLogger;
use LaravelBlinkLogger\Listeners\TransactionBeginningLogger;
use LaravelBlinkLogger\Listeners\TransactionCommittedLogger;
use LaravelBlinkLogger\Listeners\TransactionRolledBackLogger;

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
            QueryExecuted::class => QueryExecutedLogger::class,
            TransactionBeginning::class => TransactionBeginningLogger::class,
            TransactionCommitted::class => TransactionCommittedLogger::class,
            TransactionRolledBack::class => TransactionRolledBackLogger::class,
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
            'middleware' => RequestLogger::class,
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
            'middleware' => ResponseLogger::class,
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
                RequestSending::class => RequestSendingLogger::class,
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
                ResponseReceived::class => ResponseReceivedLogger::class,
            ],
        ],
    ],
];
