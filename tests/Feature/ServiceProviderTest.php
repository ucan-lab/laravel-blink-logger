<?php

declare(strict_types=1);

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LaravelBlinkLogger\Http\Middleware\RequestLogger;
use LaravelBlinkLogger\Http\Middleware\ResponseLogger;
use LaravelBlinkLogger\Listeners\QueryExecutedLogger;
use LaravelBlinkLogger\Listeners\RequestSendingLogger;
use LaravelBlinkLogger\Listeners\ResponseReceivedLogger;
use LaravelBlinkLogger\Listeners\TransactionBeginningLogger;
use LaravelBlinkLogger\Providers\LaravelBlinkLoggerServiceProvider;

it('merges blink-logger config during register', function (): void {
    $config = config('blink-logger');
    expect($config)
        ->toBeArray()
        ->toHaveKey('query')
        ->toHaveKey('http')
        ->toHaveKey('http_client');
});

it('registers publishable files under blink-logger tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(LaravelBlinkLoggerServiceProvider::class, 'blink-logger');
    expect($paths)->toBeArray()->not->toBeEmpty();
});

it('registers query listeners when query.enabled is true', function (): void {
    $listeners = [
        QueryExecuted::class => QueryExecutedLogger::class,
        'Illuminate\Database\Events\TransactionBeginning' => TransactionBeginningLogger::class,
    ];

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.query.enabled')->andReturn(true);
    $config->shouldReceive('get')->with('blink-logger.query.listeners')->andReturn($listeners);
    $config->shouldReceive('get')->with('blink-logger.http.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.response.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.enabled')->andReturn(false);

    $events = Mockery::mock(Dispatcher::class);
    $events->shouldReceive('listen')->twice();

    $router = Mockery::mock(Router::class);

    $provider = new LaravelBlinkLoggerServiceProvider($this->app);
    $provider->boot($config, $events, $router);
});

it('does not register query listeners when query.enabled is false', function (): void {
    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.query.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.response.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.enabled')->andReturn(false);

    $events = Mockery::mock(Dispatcher::class);
    $events->shouldNotReceive('listen');

    $router = Mockery::mock(Router::class);

    $provider = new LaravelBlinkLoggerServiceProvider($this->app);
    $provider->boot($config, $events, $router);
});

it('pushes request middleware to groups when http.request.enabled is true', function (): void {
    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.query.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.request.enabled')->andReturn(true);
    $config->shouldReceive('get')->with('blink-logger.http.request.middleware')->andReturn(RequestLogger::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.middleware_group_names')->andReturn(['web', 'api']);
    $config->shouldReceive('get')->with('blink-logger.http.response.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.enabled')->andReturn(false);

    $events = Mockery::mock(Dispatcher::class);

    $router = Mockery::mock(Router::class);
    $router->shouldReceive('pushMiddlewareToGroup')
        ->twice()
        ->with(Mockery::anyOf('web', 'api'), RequestLogger::class);

    $provider = new LaravelBlinkLoggerServiceProvider($this->app);
    $provider->boot($config, $events, $router);
});

it('does not push request middleware when http.request.enabled is false', function (): void {
    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.query.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.response.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.enabled')->andReturn(false);

    $events = Mockery::mock(Dispatcher::class);

    $router = Mockery::mock(Router::class);
    $router->shouldNotReceive('pushMiddlewareToGroup');

    $provider = new LaravelBlinkLoggerServiceProvider($this->app);
    $provider->boot($config, $events, $router);
});

it('pushes response middleware to groups when http.response.enabled is true', function (): void {
    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.query.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.response.enabled')->andReturn(true);
    $config->shouldReceive('get')->with('blink-logger.http.response.middleware')->andReturn(ResponseLogger::class);
    $config->shouldReceive('get')->with('blink-logger.http.response.middleware_group_names')->andReturn(['api']);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.enabled')->andReturn(false);

    $events = Mockery::mock(Dispatcher::class);

    $router = Mockery::mock(Router::class);
    $router->shouldReceive('pushMiddlewareToGroup')
        ->once()
        ->with('api', ResponseLogger::class);

    $provider = new LaravelBlinkLoggerServiceProvider($this->app);
    $provider->boot($config, $events, $router);
});

it('registers http_client request listeners when http_client.request.enabled is true', function (): void {
    $listeners = [RequestSending::class => RequestSendingLogger::class];

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.query.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.response.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.enabled')->andReturn(true);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.listeners')->andReturn($listeners);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.enabled')->andReturn(false);

    $events = Mockery::mock(Dispatcher::class);
    $events->shouldReceive('listen')
        ->once()
        ->with(RequestSending::class, RequestSendingLogger::class);

    $router = Mockery::mock(Router::class);

    $provider = new LaravelBlinkLoggerServiceProvider($this->app);
    $provider->boot($config, $events, $router);
});

it('registers http_client response listeners when http_client.response.enabled is true', function (): void {
    $listeners = [ResponseReceived::class => ResponseReceivedLogger::class];

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.query.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http.response.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.request.enabled')->andReturn(false);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.enabled')->andReturn(true);
    $config->shouldReceive('get')->with('blink-logger.http_client.response.listeners')->andReturn($listeners);

    $events = Mockery::mock(Dispatcher::class);
    $events->shouldReceive('listen')
        ->once()
        ->with(ResponseReceived::class, ResponseReceivedLogger::class);

    $router = Mockery::mock(Router::class);

    $provider = new LaravelBlinkLoggerServiceProvider($this->app);
    $provider->boot($config, $events, $router);
});

it('maps ResponseReceived to ResponseReceivedLogger in the real http_client.response config', function (): void {
    $listeners = config('blink-logger.http_client.response.listeners');

    expect($listeners)
        ->toBeArray()
        ->toHaveKey(ResponseReceived::class)
        ->and($listeners[ResponseReceived::class])->toBe(ResponseReceivedLogger::class);
});
