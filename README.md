# Laravel Blink Logger

Comprehensive Logging Tool for Laravel.

## Installation

Require this package with composer. It is recommended to only require the package for development.

```
$ composer require --dev ucan-lab/laravel-blink-logger
```

Debug log output is disabled by default, so please enable it in `.env`.

```
LOG_SQL_ENABLED=true
LOG_REQUEST_ENABLED=true
LOG_RESPONSE_ENABLED=true
```

### Option config file publish

Copy the package config to your local config with the publish command:

```
$ php artisan vendor:publish --tag=blink-logger
```

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
