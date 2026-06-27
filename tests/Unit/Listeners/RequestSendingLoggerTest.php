<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request as GuzzlePsrRequest;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Listeners\RequestSendingLogger;
use Psr\Log\LoggerInterface;

it('logs HTTP client request method and url as debug', function (): void {
    $psrRequest = new GuzzlePsrRequest('POST', 'https://api.example.com/users', [], null);
    $clientRequest = new ClientRequest($psrRequest);
    $event = new RequestSending($clientRequest);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with('POST: https://api.example.com/users', Mockery::any());

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => [
            'http_client' => [
                'response' => ['channel' => 'stack'],
            ],
        ],
    ]);

    $listener = new RequestSendingLogger($logger, $config);
    $listener->handle($event);
});

it('includes body and headers in the log context', function (): void {
    $psrRequest = new GuzzlePsrRequest(
        'GET',
        'https://api.example.com/status',
        ['X-Api-Key' => 'secret'],
        null
    );
    $clientRequest = new ClientRequest($psrRequest);
    $event = new RequestSending($clientRequest);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(
            'GET: https://api.example.com/status',
            Mockery::on(function (array $context): bool {
                return array_key_exists('body', $context) && array_key_exists('headers', $context);
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => [
            'http_client' => [
                'response' => ['channel' => 'stack'],
            ],
        ],
    ]);

    $listener = new RequestSendingLogger($logger, $config);
    $listener->handle($event);
});
