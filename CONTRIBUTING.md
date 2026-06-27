# Contributing to Laravel Blink Logger

Thanks for taking the time to contribute! This document explains how to propose changes
and the standards we follow. Contributions of all kinds are welcome — bug reports, feature
requests, documentation improvements, and pull requests.

## Language

This is an open-source project with an international audience. Please write **all** of the
following in **English**:

- Code comments and documentation
- Commit messages
- Issue titles and descriptions
- Pull request titles and descriptions

Clear, simple English is preferred so that non-native speakers can follow along.

## Requirements

| Package | Version                |
|---------|------------------------|
| PHP     | `^8.2`                 |
| Laravel | `^11.0 / ^12.0 / ^13.0` |

Dependencies are managed with [Composer](https://getcomposer.org/).

## Getting Started

1. Fork the repository and clone your fork.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create a feature branch:
   ```bash
   git switch -c feat/short-description
   ```

## Development Workflow

We follow a test-driven approach: write a failing test, make it pass, then refactor.

Before opening a pull request, make sure all of the following pass locally:

```bash
composer test              # Run the test suite (Pest)
composer analyse           # Static analysis (PHPStan / Larastan)
./vendor/bin/pint --test   # Verify code style (Laravel Pint)
composer audit             # Check dependencies for security advisories
```

To auto-fix code style, run:

```bash
./vendor/bin/pint
```

### Tests & Coverage

- New features and bug fixes must include tests.
- Test coverage must remain at **80% or higher**:
  ```bash
  ./vendor/bin/pest --coverage --min=80
  ```

## Coding Standards

- Code style is enforced by **Laravel Pint** — run it before committing.
- Static analysis must pass with no new errors (**PHPStan / Larastan**).
- Prefer immutable data: create new values instead of mutating existing objects/arrays.
- Keep functions small and focused; organize code by feature.
- Never log secrets in plain text. Redaction is a core feature of this package — when
  modifying loggers or `src/Support/Redactor.php`, preserve the existing masking behavior
  and keep the default redaction lists conservative.

## Commit Messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>: <description>
```

Allowed types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`.

Examples:

```
feat: add redaction for URL query parameters
fix: prevent slow-query threshold from logging at debug level
docs: clarify query binding redaction warning
```

## Pull Requests

1. Ensure your branch is up to date with `main`.
2. Confirm tests, static analysis, code style, and the audit check all pass.
3. Open a pull request against the `main` branch with:
   - A clear, English title following Conventional Commits style.
   - A description of **what** changed and **why**.
   - A reference to any related issue (e.g. `Closes #123`).
4. Keep pull requests focused — one logical change per PR is easier to review.

CI runs Pint, PHPStan, Composer audit, and the test matrix (PHP 8.2–8.4 against Laravel
12/13, with both `prefer-lowest` and `prefer-stable` resolutions). All checks must be green
before a PR can be merged.

## Reporting Bugs & Requesting Features

Open a [GitHub issue](https://github.com/ucan-lab/laravel-blink-logger/issues) with:

- For bugs: steps to reproduce, expected behavior, actual behavior, and your PHP/Laravel
  versions.
- For features: the problem you're trying to solve and your proposed solution.

## License

By contributing, you agree that your contributions will be licensed under the
[MIT License](https://opensource.org/licenses/MIT), the same license that covers this
project.
