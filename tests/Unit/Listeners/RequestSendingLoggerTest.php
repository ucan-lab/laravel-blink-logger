<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request as GuzzlePsrRequest;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Listeners\RequestSendingLogger;
use LaravelBlinkLogger\Support\Redactor;
use Psr\Log\LoggerInterface;

function makeNoopRequestSendingRedactor(): Redactor
{
    return new Redactor(new Repository([]));
}

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
                'request' => ['channel' => 'stack'],
            ],
        ],
    ]);

    $listener = new RequestSendingLogger($logger, $config, makeNoopRequestSendingRedactor());
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
                'request' => ['channel' => 'stack'],
            ],
        ],
    ]);

    $listener = new RequestSendingLogger($logger, $config, makeNoopRequestSendingRedactor());
    $listener->handle($event);
});

it('reads channel from request config, not response config', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/ping', [], null);
    $clientRequest = new ClientRequest($psrRequest);
    $event = new RequestSending($clientRequest);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')->once();

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('req-channel')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => [
            'http_client' => [
                'request' => ['channel' => 'req-channel'],
                'response' => ['channel' => 'res-channel'],
            ],
        ],
    ]);

    $listener = new RequestSendingLogger($logger, $config, makeNoopRequestSendingRedactor());
    $listener->handle($event);
});

it('masks sensitive query parameter in the logged HTTP client request url message', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/data?token=secret-jwt&page=1', [], null);
    $clientRequest = new ClientRequest($psrRequest);
    $event = new RequestSending($clientRequest);

    $redactor = new Redactor(new Repository([
        'blink-logger' => [
            'redact' => [
                'placeholder' => '***',
                'headers' => [],
                'body_keys' => ['token'],
            ],
        ],
    ]));

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(
            Mockery::on(function (string $message): bool {
                return str_contains($message, 'token=')
                    && ! str_contains($message, 'secret-jwt');
            }),
            Mockery::any()
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['request' => ['channel' => 'stack']]],
    ]);

    $listener = new RequestSendingLogger($logger, $config, $redactor);
    $listener->handle($event);
});

it('masks sensitive authorization header in HTTP client request log', function (): void {
    $psrRequest = new GuzzlePsrRequest(
        'GET',
        'https://api.example.com/data',
        ['Authorization' => 'Bearer secret-token'],
        null
    );
    $clientRequest = new ClientRequest($psrRequest);
    $event = new RequestSending($clientRequest);

    $redactor = new Redactor(new Repository([
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
                return $context['headers']['Authorization'] === ['***'];
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['request' => ['channel' => 'stack']]],
    ]);

    $listener = new RequestSendingLogger($logger, $config, $redactor);
    $listener->handle($event);
});

it('preserves non-sensitive headers in HTTP client request log', function (): void {
    $psrRequest = new GuzzlePsrRequest(
        'GET',
        'https://api.example.com/data',
        ['Content-Type' => 'application/json'],
        null
    );
    $clientRequest = new ClientRequest($psrRequest);
    $event = new RequestSending($clientRequest);

    $redactor = new Redactor(new Repository([
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
                return $context['headers']['Content-Type'] === ['application/json'];
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['request' => ['channel' => 'stack']]],
    ]);

    $listener = new RequestSendingLogger($logger, $config, $redactor);
    $listener->handle($event);
});
