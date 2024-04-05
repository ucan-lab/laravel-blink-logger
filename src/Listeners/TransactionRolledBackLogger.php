<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Listeners;

use Illuminate\Config\Repository;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class TransactionRolledBackLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private LoggerInterface $logger,
        private Repository $config,
    ) {
    }

    public function handle(): void
    {
        $this->logger->channel($this->config->get('blink-logger.query.channel'))->debug('ROLLBACK');
    }
}
