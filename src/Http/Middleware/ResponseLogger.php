<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseLogger
{
    /**
     * @param LogManager $logger
     */
    public function __construct(
        private Repository $config,
        private LoggerInterface $logger,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, SymfonyResponse $response): void
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

    protected function write(SymfonyResponse $response): void
    {
        $isStreamed = $response instanceof StreamedResponse
            || $response instanceof BinaryFileResponse;

        if ($isStreamed) {
            $body = '<streamed>';
        } else {
            $content = $response->getContent();
            $body = $content !== false ? $content : '';
        }

        $statusCode = $response->getStatusCode();
        $statusText = SymfonyResponse::$statusTexts[$statusCode] ?? 'unknown status';

        $this->logger->channel($this->config->get('blink-logger.http.response.channel'))->debug(sprintf(
            '%d %s',
            $statusCode,
            $statusText,
        ), [
            'body' => $body,
            'headers' => $response->headers->all(),
        ]);
    }
}
