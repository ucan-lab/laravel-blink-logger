<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Providers;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class LaravelBlinkLoggerServiceProvider extends ServiceProvider
{
    public function boot(Router $router, Kernel $kernel): void
    {
        $this->publishes([
            __DIR__ . '/../../config/blink-logger.php' => config_path('blink-logger.php'),
        ], 'blink-logger');

        $middleware = config('blink-logger.request.middleware');
        $middlewareGroupNames = config('blink-logger.request.middleware_group_names');
        foreach ($middlewareGroupNames as $middlewareGroupName) {
             $router->middlewareGroup($middlewareGroupName, [$middleware]);
        }

        $kernel->prependToMiddlewarePriority($middleware);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/blink-logger.php', 'blink-logger'
        );

        if (config('blink-logger.sql.enabled')) {
            $this->registerLegacySqlLogger();
        }
    }

    /**
     * php >= 8.1.0
     * laravel/framework >= 10.15.0
     */
    private function registerSqlLogger(): void
    {
        DB::listen(static function(QueryExecuted $event) {
            $sql = $event->connection
                ->getQueryGrammar()
                ->substituteBindingsIntoRawSql(
                    sql: $event->sql,
                    bindings: $event->connection->prepareBindings($event->bindings),
                );

            if ($event->time > config('blink-logger.sql.slow_query_time')) {
                Log::warning(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
            } else {
                Log::debug(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
            }
        });

        Event::listen(static fn (TransactionBeginning $event) => Log::debug('START TRANSACTION'));
        Event::listen(static fn (TransactionCommitted $event) => Log::debug('COMMIT'));
        Event::listen(static fn (TransactionRolledBack $event) => Log::debug('ROLLBACK'));
    }

    /**
     * For laravel/framework version 9.x
     * @deprecated
     */
    private function registerLegacySqlLogger(): void
    {
        DB::listen(static function (QueryExecuted $event): void {
            $sql = $event->sql;

            foreach ($event->bindings as $binding) {
                if (is_string($binding)) {
                    $binding = "'{$binding}'";
                } elseif (is_bool($binding)) {
                    $binding = $binding ? '1' : '0';
                } elseif (is_int($binding)) {
                    $binding = (string) $binding;
                } elseif (is_float($binding)) {
                    $binding = (string) $binding;
                } elseif ($binding === null) {
                    $binding = 'NULL';
                } elseif ($binding instanceof Carbon) {
                    $binding = "'{$binding->toDateTimeString()}'";
                } elseif ($binding instanceof DateTime) {
                    $binding = "'{$binding->format('Y-m-d H:i:s')}'";
                }

                $sql = preg_replace('/\\?/', $binding, $sql, 1);
            }

            if ($event->time > config('logging.sql.slow_query_time')) {
                Log::warning(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
            } else {
                Log::debug(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
            }
        });

        Event::listen(static fn (TransactionBeginning $event) => Log::debug('START TRANSACTION'));
        Event::listen(static fn (TransactionCommitted $event) => Log::debug('COMMIT'));
        Event::listen(static fn (TransactionRolledBack $event) => Log::debug('ROLLBACK'));
    }
}
