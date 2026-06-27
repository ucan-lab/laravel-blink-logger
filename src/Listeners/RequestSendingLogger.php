<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Listeners;

use Illuminate\Config\Repository;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Support\Redactor;
use Psr\Log\LoggerInterface;

class RequestSendingLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private LoggerInterface $logger,
        private Repository $config,
        private Redactor $redactor,
    ) {}

    public function handle(RequestSending $event): void
    {
        $this->logger->channel($this->config->get('blink-logger.http_client.request.channel'))->debug(sprintf(
            '%s: %s',
            $event->request->method(),
            $this->redactor->url($event->request->url()),
        ), [
            'body' => $this->redactor->body($event->request->data()),
            'headers' => $this->redactor->headers($event->request->headers()),
        ]);
    }
}
