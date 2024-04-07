<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LaravelBlinkLogger\Listeners\QueryExecutedLogger;
use LaravelBlinkLogger\Listeners\RequestSendingLogger;
use LaravelBlinkLogger\Listeners\ResponseReceivedLogger;
use LaravelBlinkLogger\Listeners\TransactionBeginningLogger;
use LaravelBlinkLogger\Listeners\TransactionCommittedLogger;
use LaravelBlinkLogger\Listeners\TransactionRolledBackLogger;

class LaravelBlinkLoggerServiceProvider extends ServiceProvider
{
    public function boot(Repository $config, Dispatcher $events, Router $router): void
    {
        $this->publishes([
            __DIR__ . '/../../config/blink-logger.php' => config_path('blink-logger.php'),
        ], 'blink-logger');

        // Query Logger
        if ($config->get('blink-logger.query.enabled')) {
            $events->listen(QueryExecuted::class, QueryExecutedLogger::class);
            $events->listen(TransactionBeginning::class, TransactionBeginningLogger::class);
            $events->listen(TransactionCommitted::class, TransactionCommittedLogger::class);
            $events->listen(TransactionRolledBack::class, TransactionRolledBackLogger::class);
        }

        // HTTP Request Logger
        if ($config->get('blink-logger.http.request.enabled')) {
            $middleware = $config->get('blink-logger.http.request.middleware');
            $middlewareGroupNames = $config->get('blink-logger.http.request.middleware_group_names');
            foreach ($middlewareGroupNames as $middlewareGroupName) {
                $router->pushMiddlewareToGroup($middlewareGroupName, $middleware);
            }
        }

        // HTTP Response Logger
        if ($config->get('blink-logger.http.response.enabled')) {
            $middleware = $config->get('blink-logger.http.response.middleware');
            $middlewareGroupNames = $config->get('blink-logger.http.response.middleware_group_names');
            foreach ($middlewareGroupNames as $middlewareGroupName) {
                $router->pushMiddlewareToGroup($middlewareGroupName, $middleware);
            }
        }

        // HTTP Client Request Logger
        if ($config->get('blink-logger.http_client.request.enabled')) {
            $events->listen(RequestSending::class, RequestSendingLogger::class);
        }

        // HTTP Client Response Logger
        if ($config->get('blink-logger.http_client.response.enabled')) {
            $events->listen(ResponseReceived::class, ResponseReceivedLogger::class);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/blink-logger.php', 'blink-logger'
        );
    }
}
