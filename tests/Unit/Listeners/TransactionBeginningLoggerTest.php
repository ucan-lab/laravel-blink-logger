<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Listeners\TransactionBeginningLogger;
use Psr\Log\LoggerInterface;

it('logs START TRANSACTION as debug to query channel', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with('START TRANSACTION');

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($channel);

    $config = new Repository(['blink-logger' => ['query' => ['channel' => 'stack']]]);

    $listener = new TransactionBeginningLogger($logger, $config);
    $listener->handle();
});
