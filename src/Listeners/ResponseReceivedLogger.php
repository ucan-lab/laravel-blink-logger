<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Listeners;

use Illuminate\Config\Repository;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Response;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use LaravelBlinkLogger\Support\Redactor;
use Psr\Log\LoggerInterface;

class ResponseReceivedLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private LoggerInterface $logger,
        private Repository $config,
        private Redactor $redactor,
    ) {}

    public function handle(ResponseReceived $event): void
    {
        $this->logger->channel($this->config->get('blink-logger.http_client.response.channel'))->debug(sprintf(
            '%d %s',
            $event->response->status(),
            $event->response->reason(),
        ), [
            'body' => $this->isJson($event->response) ? $this->redactor->body($event->response->json()) : $event->response->body(),
            'headers' => $this->redactor->headers($event->response->headers()),
        ]);
    }

    private function isJson(Response $response): bool
    {
        $contentType = mb_strtolower($response->header('Content-Type'));

        return Str::startsWith($contentType, 'application/json')
            || Str::startsWith($contentType, 'text/json')
            || (Str::startsWith($contentType, 'application/') && Str::contains($contentType, '+json'));
    }
}
