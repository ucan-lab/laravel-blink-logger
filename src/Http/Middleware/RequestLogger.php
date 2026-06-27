<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use LaravelBlinkLogger\Support\Redactor;
use Psr\Log\LoggerInterface;

class RequestLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private Repository $config,
        private LoggerInterface $logger,
        private Redactor $redactor,
    ) {}

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
            return $request->is(...$includePaths);
        }

        $excludePaths = $this->config->get('blink-logger.http.request.exclude_paths');
        if (count($excludePaths) > 0) {
            return ! $request->is(...$excludePaths);
        }

        return true;
    }

    protected function write(Request $request): void
    {
        $data = [
            'request' => $this->redactor->body($request->all()),
            'headers' => $this->redactor->headers($request->headers->all()),
        ];

        $this->logger->channel($this->config->get('blink-logger.http.request.channel'))
            ->debug(sprintf('%s: %s', $request->method(), $request->fullUrl()), $data);
    }
}
