<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LaravelBlinkLogger\Listeners\QueryExecutedListener;
use LaravelBlinkLogger\Listeners\TransactionBeginningListener;
use LaravelBlinkLogger\Listeners\TransactionCommittedListener;
use LaravelBlinkLogger\Listeners\TransactionRolledBackListener;

class LaravelBlinkLoggerServiceProvider extends ServiceProvider
{
    public function boot(Repository $config, Dispatcher $events, Router $router, Kernel $kernel): void
    {
        $this->publishes([
            __DIR__ . '/../../config/blink-logger.php' => config_path('blink-logger.php'),
        ], 'blink-logger');

        // Query Logger
        if ($config->get('blink-logger.query.enabled')) {
            $events->listen(QueryExecuted::class, QueryExecutedListener::class);
            $events->listen(TransactionBeginning::class, TransactionBeginningListener::class);
            $events->listen(TransactionCommitted::class, TransactionCommittedListener::class);
            $events->listen(TransactionRolledBack::class, TransactionRolledBackListener::class);
        }

        // Request Logger
        if ($config->get('blink-logger.http.request.enabled')) {
            $middleware = $config->get('blink-logger.http.request.middleware');
            $middlewareGroupNames = $config->get('blink-logger.http.request.middleware_group_names');
            foreach ($middlewareGroupNames as $middlewareGroupName) {
                $router->middlewareGroup($middlewareGroupName, [$middleware]);
            }

            $kernel->prependToMiddlewarePriority($middleware);
        }

        // Response Logger
        if ($config->get('blink-logger.http.response.enabled')) {
            $middleware = $config->get('blink-logger.http.response.middleware');
            $middlewareGroupNames = $config->get('blink-logger.http.response.middleware_group_names');
            foreach ($middlewareGroupNames as $middlewareGroupName) {
                $router->middlewareGroup($middlewareGroupName, [$middleware]);
            }

            $kernel->prependToMiddlewarePriority($middleware);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/blink-logger.php', 'blink-logger'
        );
    }
}
