<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Listeners\QueryExecutedLogger;
use Psr\Log\LoggerInterface;

beforeEach(function (): void {
    config()->set('blink-logger.query.slow_query_time', 100);
});

it('logs query as debug when execution time is below slow_query_time', function (): void {
    $connection = $this->app['db']->connection();
    $event = new QueryExecuted('select 1', [], 50.0, $connection);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(Mockery::on(fn (string $msg): bool => str_starts_with($msg, '50.00 ms, SQL:')));

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository(['blink-logger' => ['query' => ['channel' => 'stack']]]);

    $listener = new QueryExecutedLogger($logger, $config);
    $listener->handle($event);
});

it('logs query as warning when execution time exceeds slow_query_time', function (): void {
    $connection = $this->app['db']->connection();
    $event = new QueryExecuted('select 1', [], 500.0, $connection);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('warning')
        ->once()
        ->with(Mockery::on(fn (string $msg): bool => str_starts_with($msg, '500.00 ms, SQL:')));

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository(['blink-logger' => ['query' => ['channel' => 'stack']]]);

    $listener = new QueryExecutedLogger($logger, $config);
    $listener->handle($event);
});

it('expands SQL bindings into the logged message', function (): void {
    $connection = $this->app['db']->connection();
    $event = new QueryExecuted('select * from users where id = ?', [1], 50.0, $connection);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(Mockery::on(fn (string $msg): bool => str_contains($msg, 'select * from users where id =') && str_contains($msg, '1')));

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository(['blink-logger' => ['query' => ['channel' => 'stack']]]);

    $listener = new QueryExecutedLogger($logger, $config);
    $listener->handle($event);
});
