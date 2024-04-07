<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Listeners;

use Illuminate\Config\Repository;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class RequestSendingLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private LoggerInterface $logger,
        private Repository $config,
    ) {
    }

    public function handle(RequestSending $event): void
    {
        $this->logger->channel($this->config->get('blink-logger.http_client.response.channel'))->debug(sprintf(
            '%s: %s',
            $event->request->method(),
            $event->request->url(),
        ), [
            'body' => $event->request->data(),
            'headers' => $event->request->headers(),
        ]);
    }
}
