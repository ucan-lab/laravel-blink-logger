![Laravel Blink Logger](lbl_social.jpg)

# Laravel Blink Logger

[![PHP Version Require](https://poser.pugx.org/ucan-lab/laravel-blink-logger/require/php)](https://packagist.org/packages/ucan-lab/laravel-blink-logger)
[![Latest Stable Version](https://poser.pugx.org/ucan-lab/laravel-blink-logger/v)](https://packagist.org/packages/ucan-lab/laravel-blink-logger)
[![Total Downloads](https://poser.pugx.org/ucan-lab/laravel-blink-logger/downloads)](https://packagist.org/packages/ucan-lab/laravel-blink-logger)
[![Monthly Downloads](https://poser.pugx.org/ucan-lab/laravel-blink-logger/d/monthly)](https://packagist.org/packages/ucan-lab/laravel-blink-logger)
[![License](https://poser.pugx.org/ucan-lab/laravel-blink-logger/license)](https://packagist.org/packages/ucan-lab/laravel-blink-logger)

English | [日本語](README.ja.md)

Comprehensive Logging Tool for Laravel.

## Requirements

| Package    | Version            |
|------------|--------------------|
| PHP        | ^8.3               |
| Laravel    | ^12.0 / ^13.0      |

> [!NOTE]
> Laravel 11 and PHP 8.2 are no longer supported as of this major release: the minimum PHP version is raised to 8.3, and Laravel support is narrowed to 12 and 13. If you are on Laravel 11 or PHP 8.2, please upgrade before installing this version, or stay on the previous major line.

## Installation

Require this package with composer. It is recommended to only require the package for development.

```
$ composer require --dev ucan-lab/laravel-blink-logger
```

Debug log output is disabled by default, so please enable it in `.env`.

```
LOG_QUERY_ENABLED=true
LOG_HTTP_REQUEST_ENABLED=true
LOG_HTTP_RESPONSE_ENABLED=true
LOG_HTTP_CLIENT_REQUEST_ENABLED=true
LOG_HTTP_CLIENT_RESPONSE_ENABLED=true
```

### [Option] Publish the config file

Copy the package config to your local config with the publish command:

```
$ php artisan vendor:publish --tag=blink-logger
```

## Configuration

After publishing the config file, you can configure the following options in `config/blink-logger.php`.

### Redaction (`redact`)

Sensitive values are masked before they reach the log output. Redaction applies to all loggers (HTTP request/response, HTTP client request/response).

| Key | Default | Description |
|-----|---------|-------------|
| `redact.placeholder` | `***` | String used to replace redacted values. |
| `redact.headers` | See config | List of header names (case-insensitive) whose values are replaced by the placeholder. Defaults include `authorization`, `cookie`, `set-cookie`, `x-api-key`, `x-xsrf-token`, `proxy-authorization`, `php-auth-pw`, `x-auth-token`, and `x-access-token`. |
| `redact.body_keys` | See config | List of request/response body keys (case-insensitive, recursive) whose values are replaced by the placeholder. Defaults include `password`, `token`, `access_token`, `refresh_token`, `secret`, `api_key`, `authorization`, `credit_card`, `card_number`, `cvv`, `client_secret`, `private_key`, and `passphrase`. |

**URL query string masking**: Parameters in the URL query string (e.g. `?token=secret`) are also masked when their key matches `redact.body_keys`. This applies to both incoming HTTP request URLs and outgoing HTTP client request URLs.

**Non-JSON response bodies**: Body key masking is applied only when the response body is parseable as JSON (Content-Type: `application/json`, `application/ld+json`, `application/*+json`, or `text/json`). Raw string response bodies are logged as-is without key-based masking.

To customize the redact lists, publish the config file and edit `config/blink-logger.php`.

### Query Logger (`query`)

| Key | Default | Env Variable | Description |
|-----|---------|--------------|-------------|
| `query.enabled` | `false` | `LOG_QUERY_ENABLED` | Enable or disable query logging. |
| `query.channel` | `config('logging.default')` | — | Log channel to write query logs to. |
| `query.slow_query_time` | `2000` | `LOG_SQL_SLOW_QUERY_TIME` | Threshold in milliseconds. Queries that exceed this value are logged at `warning` level instead of `debug`. |
| `query.redact_bindings` | `false` | `LOG_SQL_REDACT_BINDINGS` | When `true`, SQL bindings are **not** interpolated into the query string. The raw parameterized SQL (with `?` placeholders) is logged instead, preventing binding values from appearing in logs. Defaults to `false` to preserve existing behavior. |

> [!WARNING]
> When `query.enabled` is `true` and `query.redact_bindings` is `false` (the default), SQL binding values — including passwords, tokens, and other sensitive data — are interpolated into the logged SQL string and appear in plain text in your logs. **If you enable query logging in production, set `LOG_SQL_REDACT_BINDINGS=true`** to prevent sensitive binding values from leaking into log output.
| `query.listeners` | See config | — | Map of database event classes to listener classes. Covers `QueryExecuted`, `TransactionBeginning`, `TransactionCommitted`, and `TransactionRolledBack`. |

### HTTP Request Logger (`http.request`)

| Key | Default | Env Variable | Description |
|-----|---------|--------------|-------------|
| `http.request.enabled` | `false` | `LOG_HTTP_REQUEST_ENABLED` | Enable or disable incoming HTTP request logging. |
| `http.request.channel` | `config('logging.default')` | — | Log channel to write HTTP request logs to. |
| `http.request.include_paths` | `[]` | — | If non-empty, only requests whose path matches one of these values are logged (supports `*` wildcard patterns via Laravel's `Request::is()`, e.g. `api/*`; exact strings also match). Takes precedence over `exclude_paths`. |
| `http.request.exclude_paths` | `[]` | — | When `include_paths` is empty, requests whose path matches any of these values are skipped (supports `*` wildcard patterns via Laravel's `Request::is()`, e.g. `admin/*`; exact strings also match). Has no effect when `include_paths` is non-empty. |
| `http.request.middleware_group_names` | `['web', 'api']` | — | Middleware groups the request logger middleware is registered to. |

### HTTP Response Logger (`http.response`)

| Key | Default | Env Variable | Description |
|-----|---------|--------------|-------------|
| `http.response.enabled` | `false` | `LOG_HTTP_RESPONSE_ENABLED` | Enable or disable incoming HTTP response logging. |
| `http.response.channel` | `config('logging.default')` | — | Log channel to write HTTP response logs to. |
| `http.response.include_paths` | `[]` | — | If non-empty, only responses whose path matches one of these values are logged (supports `*` wildcard patterns via Laravel's `Request::is()`, e.g. `api/*`; exact strings also match). Takes precedence over `exclude_paths`. |
| `http.response.exclude_paths` | `[]` | — | When `include_paths` is empty, responses whose path matches any of these values are skipped (supports `*` wildcard patterns via Laravel's `Request::is()`, e.g. `admin/*`; exact strings also match). Has no effect when `include_paths` is non-empty. |
| `http.response.middleware_group_names` | `['api']` | — | Middleware groups the response logger middleware is registered to. |

### HTTP Client Request Logger (`http_client.request`)

| Key | Default | Env Variable | Description |
|-----|---------|--------------|-------------|
| `http_client.request.enabled` | `false` | `LOG_HTTP_CLIENT_REQUEST_ENABLED` | Enable or disable outgoing HTTP client request logging. |
| `http_client.request.channel` | `config('logging.default')` | — | Log channel to write HTTP client request logs to. |

### HTTP Client Response Logger (`http_client.response`)

| Key | Default | Env Variable | Description |
|-----|---------|--------------|-------------|
| `http_client.response.enabled` | `false` | `LOG_HTTP_CLIENT_RESPONSE_ENABLED` | Enable or disable outgoing HTTP client response logging. |
| `http_client.response.channel` | `config('logging.default')` | — | Log channel to write HTTP client response logs to. |

## Usage

Watch the log file.

```
$ tail -f storage/logs/laravel.log
```

Example logs.

```
[2024-04-05 16:38:58] local.DEBUG: GET: http://example-app.test/api/foo/bar?baz=qux {"request":{"baz":"qux"},"headers":{"accept-language":["ja,en-US;q=0.9,en;q=0.8"],"accept-encoding":["gzip, deflate"],"accept":["text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7"],"user-agent":["Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36"],"upgrade-insecure-requests":["1"],"cache-control":["max-age=0"],"connection":["keep-alive"],"host":["example-app.test"]}} 
[2024-04-05 16:38:58] local.DEBUG: START TRANSACTION  
[2024-04-05 16:38:59] local.DEBUG: 4.01 ms, SQL: insert into `users` (`name`, `email`, `email_verified_at`, `password`, `remember_token`, `updated_at`, `created_at`) values ('Concepcion VonRueden Sr.', 'judy30@example.net', '2024-04-05 16:38:58', 'y$L7Lb.DoH7sO5Zb7RrGtSzelx6Y15gBtetVYlI4z4wB5I83oh6To1i', 'ZD34nR26LH', '2024-04-05 16:38:59', '2024-04-05 16:38:59');  
[2024-04-05 16:38:59] local.DEBUG: 3.02 ms, SQL: update `users` set `name` = 'change name', `users`.`updated_at` = '2024-04-05 16:38:59' where `id` = 122;  
[2024-04-05 16:38:59] local.DEBUG: 1.61 ms, SQL: delete from `users` where `id` = 122;  
[2024-04-05 16:38:59] local.DEBUG: 2.28 ms, SQL: insert into `users` (`name`, `email`, `email_verified_at`, `password`, `remember_token`, `updated_at`, `created_at`) values ('Delfina Brakus IV', 'anibal.cummings@example.org', '2024-04-05 16:38:59', 'y$L7Lb.DoH7sO5Zb7RrGtSzelx6Y15gBtetVYlI4z4wB5I83oh6To1i', 'Qvq73GjdiQ', '2024-04-05 16:38:59', '2024-04-05 16:38:59');  
[2024-04-05 16:38:59] local.DEBUG: COMMIT  
[2024-04-05 16:38:59] local.DEBUG: 2.15 ms, SQL: select * from `users` where `users`.`id` = 123 limit 1;  
[2024-04-05 16:38:59] local.DEBUG: 200 OK {"body":"{\"data\":\"ok\"}","headers":{"cache-control":["no-cache, private"],"date":["Fri, 05 Apr 2024 16:38:59 GMT"],"content-type":["application/json"],"x-ratelimit-limit":["60"],"x-ratelimit-remaining":["55"],"access-control-allow-origin":["*"]}} 
```
