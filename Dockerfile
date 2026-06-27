# Development/testing image for laravel-blink-logger.
# Build for a specific PHP version with: --build-arg PHP_VERSION=8.4
ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-cli

# System packages needed to build PHP extensions and run Composer.
# $PHPIZE_DEPS (provided by the official PHP image) bundles the compiler
# toolchain required by pecl/docker-php-ext-install.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        git \
        unzip \
        libzip-dev \
        libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions:
#   - pdo_sqlite: the test suite runs against an in-memory SQLite database
#   - zip:        used by Composer to extract packages
#   - pcov:       code-coverage driver (faster than Xdebug for coverage)
RUN docker-php-ext-install pdo_sqlite zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov

# Composer, copied from the official Composer image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
