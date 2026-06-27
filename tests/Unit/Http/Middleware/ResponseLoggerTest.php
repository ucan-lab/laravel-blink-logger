<?php

declare(strict_types=1);

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Http\Middleware\ResponseLogger;
use Psr\Log\LoggerInterface;

function makeResponseLogger(Repository $config, LoggerInterface|LogManager $logger): ResponseLogger
{
    return new ResponseLogger($config, $logger);
}

it('logs response via terminate when path matches include_paths', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.response.include_paths')->andReturn(['api/users']);
    $config->shouldReceive('get')->with('blink-logger.http.response.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'GET');
    $response = new Response('{"ok":true}', 200);

    $middleware = makeResponseLogger($config, $logger);
    $middleware->terminate($request, $response);
});

it('does not log response when path is not in include_paths', function (): void {
    $logger = Mockery::mock(LogManager::class);
    $logger->shouldNotReceive('channel');

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.response.include_paths')->andReturn(['api/users']);

    $request = Request::create('/api/other', 'GET');
    $response = new Response('ok', 200);

    $middleware = makeResponseLogger($config, $logger);
    $middleware->terminate($request, $response);
});

it('does not log response when path is in exclude_paths', function (): void {
    $logger = Mockery::mock(LogManager::class);
    $logger->shouldNotReceive('channel');

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.response.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.response.exclude_paths')->andReturn(['health']);

    $request = Request::create('/health', 'GET');
    $response = new Response('ok', 200);

    $middleware = makeResponseLogger($config, $logger);
    $middleware->terminate($request, $response);
});

it('logs response when path is not in exclude_paths', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.response.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.response.exclude_paths')->andReturn(['health']);
    $config->shouldReceive('get')->with('blink-logger.http.response.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'GET');
    $response = new Response('{"ok":true}', 200);

    $middleware = makeResponseLogger($config, $logger);
    $middleware->terminate($request, $response);
});

it('logs response when both include_paths and exclude_paths are empty', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.response.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.response.exclude_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.response.channel')->andReturn('stack');

    $request = Request::create('/api/anything', 'GET');
    $response = new Response('ok', 200);

    $middleware = makeResponseLogger($config, $logger);
    $middleware->terminate($request, $response);
});

it('writes status code and status text in the log message', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with('201 Created', Mockery::any());

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->andReturn($channel);

    $config = Mockery::mock(Repository::class);
    $config->shouldReceive('get')->with('blink-logger.http.response.include_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.response.exclude_paths')->andReturn([]);
    $config->shouldReceive('get')->with('blink-logger.http.response.channel')->andReturn('stack');

    $request = Request::create('/api/users', 'POST');
    $response = new Response('created', 201);

    $middleware = makeResponseLogger($config, $logger);
    $middleware->terminate($request, $response);
});
