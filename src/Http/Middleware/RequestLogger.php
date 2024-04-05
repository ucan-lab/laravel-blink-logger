<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class RequestLogger
{
    /**
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
        $includePaths = $this->config->get('blink-logger.http.request.include_paths');
        if (count($includePaths) > 0) {
            return in_array($request->path(), $includePaths, true);
        }

        $excludePaths = $this->config->get('blink-logger.http.request.exclude_paths');
        if (count($excludePaths) > 0) {
            return ! in_array($request->path(), $excludePaths, true);
        }

        return true;
    }

    protected function write(Request $request): void
    {
        $data = [
            'request' => $request->all(),
        ];

        $this->logger->channel($this->config->get('blink-logger.http.request.channel'))->debug(sprintf('%s: %s', $request->method(), $request->fullUrl()), $data);
    }
}
