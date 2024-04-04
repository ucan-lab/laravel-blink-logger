<?php

namespace LaravelBlinkLogger\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class RequestLogger
{
    /**
     * @param Repository $config
     * @param LogManager $logger
     */
    public function __construct(
        private Repository $config,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->isWrite($request)) {
            $this->write($request);
        }

        return $next($request);
    }

    protected function isWrite(Request $request): bool
    {
        return ! in_array($request->path(), $this->config->get('blink-logger.request.exclude'), true);
    }

    protected function write(Request $request): void
    {
        $data = [
            'request' => $request->all(),
        ];

        $this->logger->channel($this->config->get('blink-logger.request.channel'))->debug(sprintf('%s: %s', $request->method(), $request->fullUrl()), $data);
    }
}
