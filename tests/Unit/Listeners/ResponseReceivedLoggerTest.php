<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request as GuzzlePsrRequest;
use GuzzleHttp\Psr7\Response as GuzzlePsrResponse;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Listeners\ResponseReceivedLogger;
use LaravelBlinkLogger\Support\Redactor;
use Psr\Log\LoggerInterface;

function makeNoopResponseReceivedRedactor(): Redactor
{
    return new Redactor(new Repository([]));
}

it('logs JSON response using json() body when Content-Type is application/json', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/data');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['result' => 'ok'])
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(
            '200 OK',
            Mockery::on(function (array $context): bool {
                return $context['body'] === ['result' => 'ok'];
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

    $listener = new ResponseReceivedLogger($logger, $config, makeNoopResponseReceivedRedactor());
    $listener->handle($event);
});

it('logs non-JSON response using body() when Content-Type is not application/json', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://example.com/page');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['Content-Type' => 'text/html'],
        '<html><body>OK</body></html>'
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(
            '200 OK',
            Mockery::on(function (array $context): bool {
                return $context['body'] === '<html><body>OK</body></html>';
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

    $listener = new ResponseReceivedLogger($logger, $config, makeNoopResponseReceivedRedactor());
    $listener->handle($event);
});

it('logs JSON response using json() body when content-type header is lowercase', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/data');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['content-type' => 'application/json'],
        json_encode(['result' => 'ok'])
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with(
            '200 OK',
            Mockery::on(function (array $context): bool {
                return $context['body'] === ['result' => 'ok'];
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

    $listener = new ResponseReceivedLogger($logger, $config, makeNoopResponseReceivedRedactor());
    $listener->handle($event);
});

it('includes status code and reason phrase in log message', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/missing');
    $psrResponse = new GuzzlePsrResponse(404, ['Content-Type' => 'text/plain'], 'Not Found');
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with('404 Not Found', Mockery::any());

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => [
            'http_client' => [
                'response' => ['channel' => 'stack'],
            ],
        ],
    ]);

    $listener = new ResponseReceivedLogger($logger, $config, makeNoopResponseReceivedRedactor());
    $listener->handle($event);
});

it('treats application/ld+json as JSON and masks body keys', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/data');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['Content-Type' => 'application/ld+json'],
        json_encode(['token' => 'secret-jwt', 'name' => 'alice'])
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

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
            Mockery::any(),
            Mockery::on(function (array $context): bool {
                return $context['body']['token'] === '***'
                    && $context['body']['name'] === 'alice';
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['response' => ['channel' => 'stack']]],
    ]);

    $listener = new ResponseReceivedLogger($logger, $config, $redactor);
    $listener->handle($event);
});

it('treats application/problem+json as JSON and masks body keys', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/resource');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['Content-Type' => 'application/problem+json'],
        json_encode(['token' => 'secret-jwt', 'detail' => 'ok'])
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

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
            Mockery::any(),
            Mockery::on(function (array $context): bool {
                return $context['body']['token'] === '***'
                    && $context['body']['detail'] === 'ok';
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['response' => ['channel' => 'stack']]],
    ]);

    $listener = new ResponseReceivedLogger($logger, $config, $redactor);
    $listener->handle($event);
});

it('treats text/json as JSON and masks body keys', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/resource');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['Content-Type' => 'text/json'],
        json_encode(['token' => 'secret-jwt', 'status' => 'ok'])
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

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
            Mockery::any(),
            Mockery::on(function (array $context): bool {
                return $context['body']['token'] === '***'
                    && $context['body']['status'] === 'ok';
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['response' => ['channel' => 'stack']]],
    ]);

    $listener = new ResponseReceivedLogger($logger, $config, $redactor);
    $listener->handle($event);
});

it('masks sensitive authorization header in HTTP client response log', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/data');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['Content-Type' => 'text/plain', 'Authorization' => 'Bearer secret'],
        'ok'
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

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
                return $context['headers']['Authorization'] === ['***']
                    && $context['headers']['Content-Type'] === ['text/plain'];
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['response' => ['channel' => 'stack']]],
    ]);

    $listener = new ResponseReceivedLogger($logger, $config, $redactor);
    $listener->handle($event);
});

it('masks sensitive body keys in JSON HTTP client response log', function (): void {
    $psrRequest = new GuzzlePsrRequest('GET', 'https://api.example.com/auth');
    $psrResponse = new GuzzlePsrResponse(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['token' => 'secret-jwt', 'user' => 'alice'])
    );
    $event = new ResponseReceived(new ClientRequest($psrRequest), new ClientResponse($psrResponse));

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
            Mockery::any(),
            Mockery::on(function (array $context): bool {
                return $context['body']['token'] === '***'
                    && $context['body']['user'] === 'alice';
            })
        );

    $logger = Mockery::mock(LogManager::class);
    $logger->shouldReceive('channel')->with('stack')->andReturn($channel);

    $config = new Repository([
        'blink-logger' => ['http_client' => ['response' => ['channel' => 'stack']]],
    ]);

    $listener = new ResponseReceivedLogger($logger, $config, $redactor);
    $listener->handle($event);
});
