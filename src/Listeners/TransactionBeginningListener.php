<?php

namespace LaravelBlinkLogger\Listeners;

use Illuminate\Config\Repository;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class TransactionBeginningListener
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
        $this->logger->channel($this->config->get('blink-logger.sql.channel'))->debug('START TRANSACTION');
    }
}
