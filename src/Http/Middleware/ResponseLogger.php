<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

class ResponseLogger
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
        return $next($request);
    }

    public function terminate(Request $request, Response|JsonResponse $response): void
    {
        if ($this->isWrite($request)) {
            $this->write($response);
        }
    }

    protected function isWrite(Request $request): bool
    {
        $includePaths = $this->config->get('blink-logger.http.response.include_paths');
        if (count($includePaths) > 0) {
            return in_array($request->path(), $includePaths, true);
        }

        $excludePaths = $this->config->get('blink-logger.http.response.exclude_paths');
        if (count($excludePaths) > 0) {
            return ! in_array($request->path(), $excludePaths, true);
        }

        return true;
    }

    protected function write(Response|JsonResponse $response): void
    {
        $this->logger->channel($this->config->get('blink-logger.http.response.channel'))->debug(sprintf(
            '%d %s',
            $response->status(),
            $response->statusText(),
        ), [
            'body' => $response->content(),
            'headers' => $response->headers->all(),
        ]);
    }
}
