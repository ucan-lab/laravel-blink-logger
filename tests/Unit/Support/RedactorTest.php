<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use LaravelBlinkLogger\Support\Redactor;

function makeRedactor(array $redactConfig = []): Redactor
{
    $config = new Repository([
        'blink-logger' => [
            'redact' => array_merge([
                'placeholder' => '***',
                'headers' => ['authorization', 'cookie', 'x-api-key'],
                'body_keys' => ['password', 'token', 'secret'],
            ], $redactConfig),
        ],
    ]);

    return new Redactor($config);
}

// --- headers() ---

it('masks a matching header (case-insensitive key)', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->headers(['Authorization' => ['Bearer secret-token']]);

    expect($result['Authorization'])->toBe(['***']);
});

it('masks a matching header stored as a string value', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->headers(['cookie' => 'session=abc123']);

    expect($result['cookie'])->toBe('***');
});

it('masks a matching header when config key uses different case', function (): void {
    $redactor = makeRedactor(['headers' => ['AUTHORIZATION']]);

    $result = $redactor->headers(['authorization' => ['Bearer token']]);

    expect($result['authorization'])->toBe(['***']);
});

it('preserves array structure when masking array header values', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->headers(['authorization' => ['token-a', 'token-b']]);

    expect($result['authorization'])->toBe(['***', '***']);
    expect(count($result['authorization']))->toBe(2);
});

it('does not mask non-sensitive headers', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->headers([
        'content-type' => ['application/json'],
        'accept' => ['*/*'],
    ]);

    expect($result['content-type'])->toBe(['application/json']);
    expect($result['accept'])->toBe(['*/*']);
});

it('masks sensitive headers while preserving non-sensitive headers', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->headers([
        'authorization' => ['Bearer token'],
        'content-type' => ['application/json'],
    ]);

    expect($result['authorization'])->toBe(['***']);
    expect($result['content-type'])->toBe(['application/json']);
});

it('returns a new array without mutating the original', function (): void {
    $redactor = makeRedactor();
    $original = ['authorization' => ['Bearer token'], 'host' => ['example.com']];

    $result = $redactor->headers($original);

    expect($original['authorization'])->toBe(['Bearer token']);
    expect($result['authorization'])->toBe(['***']);
});

it('returns empty array for empty headers input', function (): void {
    $redactor = makeRedactor();

    expect($redactor->headers([]))->toBe([]);
});

it('uses custom placeholder from config', function (): void {
    $redactor = makeRedactor(['placeholder' => '[REDACTED]', 'headers' => ['authorization'], 'body_keys' => []]);

    $result = $redactor->headers(['authorization' => 'Bearer token']);

    expect($result['authorization'])->toBe('[REDACTED]');
});

it('falls back to empty redact list when config is missing', function (): void {
    $redactor = new Redactor(new Repository([]));

    $result = $redactor->headers(['authorization' => 'Bearer token']);

    expect($result['authorization'])->toBe('Bearer token');
});

// --- body() ---

it('masks a matching body key', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->body(['username' => 'alice', 'password' => 'supersecret']);

    expect($result['password'])->toBe('***');
    expect($result['username'])->toBe('alice');
});

it('masks a matching body key case-insensitively', function (): void {
    $redactor = makeRedactor(['headers' => [], 'body_keys' => ['PASSWORD']]);

    $result = $redactor->body(['password' => 'secret']);

    expect($result['password'])->toBe('***');
});

it('does not mask non-sensitive body keys', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->body(['email' => 'alice@example.com', 'name' => 'Alice']);

    expect($result['email'])->toBe('alice@example.com');
    expect($result['name'])->toBe('Alice');
});

it('masks nested body keys recursively', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->body([
        'user' => [
            'name' => 'Alice',
            'password' => 'secret',
        ],
    ]);

    expect($result['user']['password'])->toBe('***');
    expect($result['user']['name'])->toBe('Alice');
});

it('masks deeply nested body keys', function (): void {
    $redactor = makeRedactor();

    $result = $redactor->body([
        'data' => [
            'auth' => [
                'token' => 'my-jwt',
                'type' => 'bearer',
            ],
        ],
    ]);

    expect($result['data']['auth']['token'])->toBe('***');
    expect($result['data']['auth']['type'])->toBe('bearer');
});

it('returns non-array body as-is', function (): void {
    $redactor = makeRedactor();

    expect($redactor->body('raw string'))->toBe('raw string');
    expect($redactor->body(null))->toBeNull();
    expect($redactor->body(42))->toBe(42);
});

it('returns a new array without mutating the original body', function (): void {
    $redactor = makeRedactor();
    $original = ['password' => 'secret', 'name' => 'Alice'];

    $result = $redactor->body($original);

    expect($original['password'])->toBe('secret');
    expect($result['password'])->toBe('***');
});

it('returns empty array for empty body input', function (): void {
    $redactor = makeRedactor();

    expect($redactor->body([]))->toBe([]);
});

it('falls back to empty redact list for body when config is missing', function (): void {
    $redactor = new Redactor(new Repository([]));

    $result = $redactor->body(['password' => 'secret']);

    expect($result['password'])->toBe('secret');
});
