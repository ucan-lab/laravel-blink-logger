<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Listeners;

use Illuminate\Config\Repository;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class QueryExecutedLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private LoggerInterface $logger,
        private Repository $config,
    ) {
    }

    public function handle(QueryExecuted $event): void
    {
        $sql = $event->connection
            ->getQueryGrammar()
            ->substituteBindingsIntoRawSql(
                sql: $event->sql,
                bindings: $event->connection->prepareBindings($event->bindings),
            );

        if ($event->time > config('blink-logger.query.slow_query_time')) {
            $this->logger->channel($this->config->get('blink-logger.query.channel'))->warning(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
        } else {
            $this->logger->channel($this->config->get('blink-logger.query.channel'))->debug(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
        }
    }
}
