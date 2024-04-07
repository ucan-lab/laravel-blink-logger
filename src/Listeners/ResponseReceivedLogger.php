<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Listeners;

use Illuminate\Config\Repository;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Response;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class ResponseReceivedLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private LoggerInterface $logger,
        private Repository $config,
    ) {
    }

    public function handle(ResponseReceived $event): void
    {
        $this->logger->channel($this->config->get('blink-logger.http_client.response.channel'))->debug(sprintf(
            '%d %s',
            $event->response->status(),
            $event->response->reason(),
        ), [
            'body' => $this->isJson($event->response) ? $event->response->json() : $event->response->body(),
            'headers' => $event->response->headers(),
        ]);
    }

    private function isJson(Response $response): bool
    {
        return Str::startsWith($response->headers()['Content-Type'][0] ?? '', 'application/json');
    }
}
