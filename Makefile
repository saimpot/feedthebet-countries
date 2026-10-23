.DEFAULT_GOAL := help
.PHONY: help up down build install test backend-test frontend-test lint warm logs sh-php sh-node

COMPOSE  ?= docker compose
PHP_RUN  = $(COMPOSE) run --rm --user app php
NODE_RUN = $(COMPOSE) run --rm node

help: ## Show available targets
	@grep -E '^[a-z-]+:.*##' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-15s %s\n", $$1, $$2}'

up: ## Start all services in the background
	$(COMPOSE) up -d

down: ## Stop and remove containers
	$(COMPOSE) down

build: ## Build the docker images
	$(COMPOSE) build

install: build ## First-time setup: env files, composer + npm install, app key
	@test -f .env || cp .env.example .env
	@test -f backend/.env || cp backend/.env.example backend/.env
	$(PHP_RUN) composer install --no-interaction
	$(PHP_RUN) php artisan key:generate --ansi
	$(NODE_RUN) npm install
	$(COMPOSE) up -d

test: backend-test frontend-test ## Run Pest and Vitest

backend-test: ## Run the Pest suite
	$(PHP_RUN) ./vendor/bin/pest

frontend-test: ## Run the Vitest suite
	$(NODE_RUN) npm test

lint: ## Run Pint, Larastan, ESLint/oxlint and the TypeScript type-check
	$(PHP_RUN) composer lint
	$(PHP_RUN) composer analyse
	$(NODE_RUN) npm run lint
	$(NODE_RUN) npm run type-check

warm: ## Warm the countries cache
	$(COMPOSE) exec -T --user app php php artisan countries:warm

logs: ## Tail service logs
	$(COMPOSE) logs -f --tail=100

sh-php: ## Shell in the php container
	$(COMPOSE) exec --user app php sh

sh-node: ## Shell in the node container
	$(COMPOSE) exec node sh

PROD_COMPOSE ?= docker compose -p feedthebet-prod -f docker-compose.prod.yml

.PHONY: prod-build prod-up prod-down

prod-build: ## Build the production images
	$(PROD_COMPOSE) build

prod-up: ## Start the production stack (APP_PORT, default 8080)
	$(PROD_COMPOSE) up -d --build

prod-down: ## Stop the production stack and remove its volumes
	$(PROD_COMPOSE) down -v
