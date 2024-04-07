<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class LaravelBlinkLoggerServiceProvider extends ServiceProvider
{
    public function boot(Repository $config, Dispatcher $events, Router $router): void
    {
        $this->publishes([
            __DIR__ . '/../../config/blink-logger.php' => config_path('blink-logger.php'),
        ], 'blink-logger');

        // Query Logger
        if ($config->get('blink-logger.query.enabled')) {
            foreach ($config->get('blink-logger.query.listeners') as $event => $listener) {
                $events->listen($event, $listener);
            }
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
            foreach ($config->get('blink-logger.http_client.request.listeners') as $event => $listener) {
                $events->listen($event, $listener);
            }
        }

        // HTTP Client Response Logger
        if ($config->get('blink-logger.http_client.response.enabled')) {
            foreach ($config->get('blink-logger.http_client.response.listeners') as $event => $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/blink-logger.php', 'blink-logger'
        );
    }
}
