# Docker-based developer shortcuts.
# Override the PHP version for any target with: make PHP_VERSION=8.4 <target>
PHP_VERSION ?= 8.3
export PHP_VERSION

DC  = docker compose
RUN = $(DC) run --rm app

.PHONY: help build install test coverage analyse pint pint-test audit validate ci shell

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

build: ## Build the Docker image
	$(DC) build

install: ## Install dependencies (composer update; this is a library, no lock file)
	$(RUN) composer update --prefer-stable --no-interaction --no-progress

test: ## Run the test suite (Pest)
	$(RUN) ./vendor/bin/pest

coverage: ## Run the test suite with the 80% coverage gate (pcov)
	$(RUN) ./vendor/bin/pest --coverage --min=80

analyse: ## Run static analysis (PHPStan / Larastan)
	$(RUN) composer analyse

pint: ## Auto-format the code (Laravel Pint)
	$(RUN) ./vendor/bin/pint

pint-test: ## Check formatting without modifying files
	$(RUN) ./vendor/bin/pint --test

audit: ## Check dependencies for security advisories
	$(RUN) composer audit

validate: ## Validate composer.json
	$(RUN) composer validate --no-check-publish

ci: pint-test analyse audit coverage validate ## Run all CI checks

shell: ## Open a shell in the container
	$(RUN) bash
