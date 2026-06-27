<?php

declare(strict_types=1);

namespace LaravelBlinkLogger\Support;

use Illuminate\Contracts\Config\Repository;

class Redactor
{
    public function __construct(
        private Repository $config,
    ) {}

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function headers(array $headers): array
    {
        $raw = $this->config->get('blink-logger.redact.headers');
        $redactList = array_map(
            static fn (mixed $h): string => mb_strtolower((string) $h),
            is_array($raw) ? $raw : [],
        );
        $placeholder = $this->placeholder();

        $result = [];
        foreach ($headers as $name => $value) {
            if (in_array(mb_strtolower($name), $redactList, true)) {
                $result[$name] = is_array($value)
                    ? array_fill(0, count($value), $placeholder)
                    : $placeholder;
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    public function body(mixed $body): mixed
    {
        if (! is_array($body)) {
            return $body;
        }

        $raw = $this->config->get('blink-logger.redact.body_keys');
        $redactKeys = array_map(
            static fn (mixed $k): string => mb_strtolower((string) $k),
            is_array($raw) ? $raw : [],
        );

        return $this->redactArray($body, $redactKeys, $this->placeholder());
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<string> $redactKeys
     * @return array<array-key, mixed>
     */
    private function redactArray(array $data, array $redactKeys, string $placeholder): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array(mb_strtolower((string) $key), $redactKeys, true)) {
                $result[$key] = $placeholder;
            } elseif (is_array($value)) {
                $result[$key] = $this->redactArray($value, $redactKeys, $placeholder);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function placeholder(): string
    {
        $raw = $this->config->get('blink-logger.redact.placeholder');

        return is_string($raw) ? $raw : '***';
    }
}
