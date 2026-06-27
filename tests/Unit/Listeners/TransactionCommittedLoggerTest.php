<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Listeners\TransactionCommittedLogger;
use Psr\Log\LoggerInterface;

it('logs COMMIT as debug to query channel', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with('COMMIT');

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($channel);

    $config = new Repository(['blink-logger' => ['query' => ['channel' => 'stack']]]);

    $listener = new TransactionCommittedLogger($logger, $config);
    $listener->handle();
});
