<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Listeners;

use Carbon\Carbon;
use DateTime;
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
        $sql = $event->sql;

        foreach ($event->bindings as $binding) {
            if (is_string($binding)) {
                $binding = "'{$binding}'";
            } elseif (is_bool($binding)) {
                $binding = $binding ? '1' : '0';
            } elseif (is_int($binding)) {
                $binding = (string) $binding;
            } elseif (is_float($binding)) {
                $binding = (string) $binding;
            } elseif ($binding === null) {
                $binding = 'NULL';
            } elseif ($binding instanceof Carbon) {
                $binding = "'{$binding->toDateTimeString()}'";
            } elseif ($binding instanceof DateTime) {
                $binding = "'{$binding->format('Y-m-d H:i:s')}'";
            }

            $sql = preg_replace('/\\?/', $binding, $sql, 1);
        }

        if ($event->time > $this->config->get('blink-logger.query.slow_query_time')) {
            $this->logger->channel($this->config->get('blink-logger.query.channel'))->warning(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
        } else {
            $this->logger->channel($this->config->get('blink-logger.query.channel'))->debug(sprintf('%.2f ms, SQL: %s;', $event->time, $sql));
        }
    }
}
