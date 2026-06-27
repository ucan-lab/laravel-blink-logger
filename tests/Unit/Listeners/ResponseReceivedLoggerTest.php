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
use Psr\Log\LoggerInterface;

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

    $listener = new ResponseReceivedLogger($logger, $config);
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

    $listener = new ResponseReceivedLogger($logger, $config);
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

    $listener = new ResponseReceivedLogger($logger, $config);
    $listener->handle($event);
});
