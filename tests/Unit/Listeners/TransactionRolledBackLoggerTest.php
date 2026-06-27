<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Listeners\TransactionRolledBackLogger;
use Psr\Log\LoggerInterface;

it('logs ROLLBACK as debug to query channel', function (): void {
    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with('ROLLBACK');

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($channel);

    $config = new Repository(['blink-logger' => ['query' => ['channel' => 'stack']]]);

    $listener = new TransactionRolledBackLogger($logger, $config);
    $listener->handle();
});
