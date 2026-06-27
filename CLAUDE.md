# CLAUDE.md

Guidance for Claude Code (and other AI agents) when working in this repository.

## Project Overview

Laravel Blink Logger is an open-source Laravel package that provides comprehensive,
configuration-driven logging for SQL queries, database transactions, incoming HTTP
requests/responses, and outgoing HTTP client requests/responses. Sensitive values are
redacted before they reach the log output.

It is distributed as a Composer library (`ucan-lab/laravel-blink-logger`) and is meant to
be required as a dev dependency by Laravel applications.

## Language & Communication Policy

This is an open-source project intended to be approachable for an international audience.
**Always write the following in English:**

- Source code comments and documentation
- Commit messages
- GitHub issues and pull request titles/descriptions
- Code identifiers (class, method, variable names)

Keep wording clear and concise so non-native speakers can follow it easily.

## Commands

Dependencies are managed with Composer. Common tasks:

```bash
composer install            # Install dependencies
composer test               # Run the test suite (Pest)
composer analyse            # Static analysis (PHPStan / Larastan)
./vendor/bin/pint           # Auto-format code (Laravel Pint)
./vendor/bin/pint --test    # Check formatting without modifying files
./vendor/bin/pest --coverage --min=80   # Run tests with coverage gate
composer audit              # Check dependencies for security advisories
```

> Note: `composer.lock` is intentionally gitignored (this is a library, not an app).

## Architecture

- **`src/Providers/LaravelBlinkLoggerServiceProvider.php`** — Entry point. Publishes the
  config, registers event listeners and middleware based on the `config/blink-logger.php`
  settings. Auto-discovered via the `extra.laravel.providers` entry in `composer.json`.
- **`src/Listeners/`** — Listeners bound to Laravel events:
  - `QueryExecutedLogger` logs SQL queries (and flags slow queries as warnings).
  - `TransactionBeginningLogger` / `TransactionCommittedLogger` / `TransactionRolledBackLogger`
    log DB transaction lifecycle events.
  - `RequestSendingLogger` / `ResponseReceivedLogger` log outgoing HTTP client traffic.
- **`src/Http/Middleware/`** — `RequestLogger` and `ResponseLogger` log incoming HTTP
  requests/responses; registered into middleware groups configured in `config`.
- **`src/Support/Redactor.php`** — Masks sensitive headers, body keys, and URL query
  parameters before logging. Used by all loggers.
- **`config/blink-logger.php`** — Single source of truth for enable flags (env-driven),
  log channels, redaction lists, path include/exclude filters, and slow-query threshold.

Each logger is independently toggleable via `LOG_*_ENABLED` environment variables and is
**disabled by default**.

## Conventions

- **Commits:** Conventional Commits — `<type>: <description>` (`feat`, `fix`, `refactor`,
  `docs`, `test`, `chore`, `perf`, `ci`).
- **Code style:** Enforced by Laravel Pint. Run `./vendor/bin/pint` before committing.
- **Static analysis:** Must pass `composer analyse` (PHPStan / Larastan) with no new errors.
- **Tests:** Written with Pest. New behavior requires tests; coverage must stay at 80%+.
- **Immutability:** Prefer creating new values over mutating existing objects/arrays.
- **Supported versions:** PHP `^8.2`, Laravel `^11.0 | ^12.0 | ^13.0`. The CI matrix tests
  PHP 8.2–8.4 against Laravel 12/13 with both lowest and stable dependency resolutions, so
  avoid APIs unavailable in the lowest supported versions.

## Security

Redaction is a core feature — be cautious when touching loggers or `Redactor`. Never log
secrets in plain text, and keep the default redaction lists conservative. When query
logging is enabled, binding values can leak into logs unless `query.redact_bindings` is set;
preserve that safety behavior and its documentation.

See `CONTRIBUTING.md` for the full contributor workflow.
