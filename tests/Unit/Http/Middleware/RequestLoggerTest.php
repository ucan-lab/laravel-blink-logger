<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Http\Middleware\RequestLogger;
use LaravelBlinkLogger\Support\Redactor;
use Psr\Log\LoggerInterface;

function makeRequestLogger(Repository $config, LoggerInterface|LogManager $logger, ?Redactor $redactor = null): RequestLogger
{
    return new RequestLogger($config, $logger, $redactor ?? new Redactor(new ConfigRepository([])));
}

it('logs request when path matches include_paths', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn(['api/users']);
    $config->shouldReceive('get')->with('blink-logger.http.request.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('does not log request when path is not in include_paths', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldNotReceive('debug');

    $logger = Mockery::mock(LogManager::class);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn(['api/users']);

    $request = Request::create('/api/other', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('does not log request when path is in exclude_paths', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldNotReceive('debug');

    $logger = Mockery::mock(LogManager::class);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.exclude_paths')->andReturn(['health']);

    $request = Request::create('/health', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('logs request when path is not in exclude_paths', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.exclude_paths')->andReturn(['health']);
    $config->shouldReceive('get')->with('blink-logger.http.request.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('logs request when both include_paths and exclude_paths are empty', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.exclude_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.channel')->andReturn('stack');

    $request = Request::create('/api/anything', 'POST');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('logs request when path matches include_paths wildcard', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn(['api/*']);
    $config->shouldReceive('get')->with('blink-logger.http.request.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('does not log request when path does not match include_paths wildcard', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldNotReceive('debug');

    $logger = Mockery::mock(LogManager::class);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn(['api/*']);

    $request = Request::create('/admin/dashboard', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('does not log request when path matches exclude_paths wildcard', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldNotReceive('debug');

    $logger = Mockery::mock(LogManager::class);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.exclude_paths')->andReturn(['admin/*']);

    $request = Request::create('/admin/dashboard', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('include_paths takes priority over exclude_paths', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    // include_paths is non-empty, so exclude_paths is never checked
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn(['api/users']);
    $config->shouldReceive('get')->with('blink-logger.http.request.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'GET');
    $middleware = makeRequestLogger($config, $logger);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('masks sensitive body keys in the logged request context', function (): void {
    $redactor = new Redactor(new ConfigRepository([
        'blink-logger' => [
            'redact' => [
                'placeholder' => '***',
                'headers' => [],
                'body_keys' => ['password'],
            ],
        ],
    ]));

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::on(function (array $context): bool {
                return $context['request']['password'] === '***'
                    && $context['request']['name'] === 'Alice';
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.exclude_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'POST', ['name' => 'Alice', 'password' => 'supersecret']);
    $middleware = makeRequestLogger($config, $logger, $redactor);
    $middleware->handle($request, fn ($r) => response('ok'));
});

it('masks sensitive headers in the logged request context', function (): void {
    $redactor = new Redactor(new ConfigRepository([
        'blink-logger' => [
            'redact' => [
                'placeholder' => '***',
                'headers' => ['authorization'],
                'body_keys' => [],
            ],
        ],
    ]));

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(
            Mockery::any(),
            Mockery::on(function (array $context): bool {
                return $context['headers']['authorization'] === ['***']
                    && $context['headers']['host'] === ['example.com'];
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.request.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.exclude_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.request.channel')->andReturn('stack');

    $request = Request::create('http://example.com/api/users', 'GET');
    $request->headers->set('Authorization', 'Bearer my-secret-token');
    $middleware = makeRequestLogger($config, $logger, $redactor);
    $middleware->handle($request, fn ($r) => response('ok'));
});
