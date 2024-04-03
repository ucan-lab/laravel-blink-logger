<?php

namespace LaravelBlinkLogger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLogger
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->isWrite($request)) {
            $this->write($request);
        }

        return $next($request);
    }

    protected function isWrite(Request $request): bool
    {
        return ! in_array($request->path(), config('blink-logger.request.exclude'), true);
    }

    protected function write(Request $request): void
    {
        $data = [
            'request' => $request->all(),
        ];

        Log::channel(config('blink-logger.request.channel'))->debug(sprintf('%s: %s', $request->method(), $request->fullUrl()), $data);
    }
}
