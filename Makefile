# Laravel Homeschool Learning App - Makefile
# Common development and deployment commands

# Variables
PHP = php
COMPOSER = composer
NPM = npm
ARTISAN = $(PHP) artisan
PORT ?= 8000

# Colors for output
GREEN = \033[0;32m
YELLOW = \033[1;33m
RED = \033[0;31m
NC = \033[0m # No Color

# Default target
.DEFAULT_GOAL := help

## Help command
help: ## Show this help message
	@echo "$(GREEN)Laravel Homeschool Learning App - Available Commands$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(GREEN)Usage:$(NC) make [command]"

## Development Environment
.PHONY: install
install: ## Install all dependencies (Composer + NPM)
	@echo "$(GREEN)Installing Composer dependencies...$(NC)"
	$(COMPOSER) install
	@echo "$(GREEN)Installing NPM dependencies...$(NC)"
	$(NPM) install
	@echo "$(GREEN)Building frontend assets...$(NC)"
	$(NPM) run build
	@echo "$(GREEN)Dependencies installed successfully!$(NC)"

.PHONY: dev
dev: ## Start development server (Laravel + Vite)
	@echo "$(GREEN)Starting development environment...$(NC)"
	@echo "$(YELLOW)Laravel server: http://localhost:$(PORT)$(NC)"
	$(ARTISAN) serve --port=$(PORT)

.PHONY: dev-all
dev-all: ## Start all development services (Laravel + Vite + Queue + Logs)
	@echo "$(GREEN)Starting all development services...$(NC)"
	$(COMPOSER) run dev

.PHONY: vite
vite: ## Start Vite dev server for frontend assets
	@echo "$(GREEN)Starting Vite development server...$(NC)"
	$(NPM) run dev

.PHONY: build
build: ## Build frontend assets for production
	@echo "$(GREEN)Building frontend assets for production...$(NC)"
	$(NPM) run build

## Database Operations
.PHONY: db-fresh
db-fresh: ## Drop all tables and re-run migrations with seeders
	@echo "$(YELLOW)⚠️  This will delete all data! Press Ctrl+C to cancel...$(NC)"
	@sleep 3
	@echo "$(GREEN)Refreshing database...$(NC)"
	$(ARTISAN) migrate:fresh --seed

.PHONY: migrate
migrate: ## Run database migrations
	@echo "$(GREEN)Running migrations...$(NC)"
	$(ARTISAN) migrate

.PHONY: rollback
rollback: ## Rollback last database migration
	@echo "$(YELLOW)Rolling back last migration...$(NC)"
	$(ARTISAN) migrate:rollback

.PHONY: seed
seed: ## Seed the database
	@echo "$(GREEN)Seeding database...$(NC)"
	$(ARTISAN) db:seed

.PHONY: db-reset
db-reset: ## Reset database (rollback all migrations and re-run)
	@echo "$(YELLOW)Resetting database...$(NC)"
	$(ARTISAN) migrate:reset
	$(ARTISAN) migrate
	$(ARTISAN) db:seed

## Testing
.PHONY: test
test: ## Run all tests (PHPUnit + E2E)
	@echo "$(GREEN)Running all tests...$(NC)"
	@$(MAKE) test-unit
	@$(MAKE) test-e2e

.PHONY: test-unit
test-unit: ## Run PHPUnit tests (SAFE - uses test database)
	@echo "$(GREEN)Running PHPUnit tests on TEST database...$(NC)"
	@echo "$(YELLOW)⚠️  Using safe test runner to protect development database$(NC)"
	@./scripts/safe-test.sh

.PHONY: test-unit-coverage
test-unit-coverage: ## Run PHPUnit tests with coverage (SAFE - uses test database)
	@echo "$(GREEN)Running PHPUnit tests with coverage on TEST database...$(NC)"
	@./scripts/safe-test.sh --coverage

.PHONY: test-e2e
test-e2e: ## Run E2E tests with Playwright
	@echo "$(GREEN)Running E2E tests...$(NC)"
	$(NPM) run test:e2e

.PHONY: test-e2e-headed
test-e2e-headed: ## Run E2E tests with browser visible
	@echo "$(GREEN)Running E2E tests with browser...$(NC)"
	$(NPM) run test:e2e:headed

.PHONY: test-e2e-ui
test-e2e-ui: ## Open Playwright test UI
	@echo "$(GREEN)Opening Playwright test UI...$(NC)"
	$(NPM) run test:ui

.PHONY: test-specific
test-specific: ## Run specific test file (use TEST=path/to/test)
	@echo "$(GREEN)Running specific test: $(TEST)$(NC)"
	$(NPM) run test:e2e -- $(TEST)

## Code Quality
.PHONY: lint
lint: ## Run code linters (PHP + JS)
	@echo "$(GREEN)Running linters...$(NC)"
	@$(MAKE) lint-php
	@$(MAKE) lint-js

.PHONY: lint-php
lint-php: ## Run PHP linter (Pint)
	@echo "$(GREEN)Running PHP linter...$(NC)"
	./vendor/bin/pint

.PHONY: lint-js
lint-js: ## Run JavaScript linter
	@echo "$(GREEN)Running JavaScript linter...$(NC)"
	$(NPM) run lint

.PHONY: lint-fix
lint-fix: ## Auto-fix linting issues
	@echo "$(GREEN)Auto-fixing linting issues...$(NC)"
	./vendor/bin/pint
	$(NPM) run lint:fix

.PHONY: format
format: ## Format code with Prettier
	@echo "$(GREEN)Formatting code...$(NC)"
	$(NPM) run format

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	@echo "$(GREEN)Running PHPStan static analysis...$(NC)"
	./vendor/bin/phpstan analyse --memory-limit=512M

.PHONY: type-check
type-check: ## Run TypeScript type checking
	@echo "$(GREEN)Running TypeScript type checking...$(NC)"
	$(NPM) run type-check

## Cache and Optimization
.PHONY: cache-clear
cache-clear: ## Clear all caches
	@echo "$(GREEN)Clearing all caches...$(NC)"
	$(ARTISAN) cache:clear
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear
	@echo "$(GREEN)All caches cleared!$(NC)"

.PHONY: optimize
optimize: ## Optimize application for production
	@echo "$(GREEN)Optimizing application...$(NC)"
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	@echo "$(GREEN)Application optimized!$(NC)"

.PHONY: optimize-clear
optimize-clear: ## Clear optimization caches
	@echo "$(YELLOW)Clearing optimization caches...$(NC)"
	$(ARTISAN) optimize:clear

## Queue Management
.PHONY: queue
queue: ## Start queue worker
	@echo "$(GREEN)Starting queue worker...$(NC)"
	$(ARTISAN) queue:work

.PHONY: queue-listen
queue-listen: ## Start queue listener (auto-restart on code changes)
	@echo "$(GREEN)Starting queue listener...$(NC)"
	$(ARTISAN) queue:listen

.PHONY: queue-restart
queue-restart: ## Restart queue workers
	@echo "$(YELLOW)Restarting queue workers...$(NC)"
	$(ARTISAN) queue:restart

## Logs and Debugging
.PHONY: logs
logs: ## Tail Laravel logs
	@echo "$(GREEN)Tailing Laravel logs...$(NC)"
	tail -f storage/logs/laravel.log

.PHONY: logs-clear
logs-clear: ## Clear Laravel logs
	@echo "$(YELLOW)Clearing Laravel logs...$(NC)"
	rm -f storage/logs/*.log
	@echo "$(GREEN)Logs cleared!$(NC)"

.PHONY: pail
pail: ## Start Laravel Pail log viewer
	@echo "$(GREEN)Starting Laravel Pail...$(NC)"
	$(ARTISAN) pail

## Artisan Commands
.PHONY: tinker
tinker: ## Start Laravel Tinker REPL
	@echo "$(GREEN)Starting Laravel Tinker...$(NC)"
	$(ARTISAN) tinker

.PHONY: routes
routes: ## List all routes
	@echo "$(GREEN)Listing all routes...$(NC)"
	$(ARTISAN) route:list

.PHONY: storage-link
storage-link: ## Create storage symlink
	@echo "$(GREEN)Creating storage symlink...$(NC)"
	$(ARTISAN) storage:link

## Git Hooks
.PHONY: hooks-install
hooks-install: ## Install git hooks (Husky)
	@echo "$(GREEN)Installing git hooks...$(NC)"
	npx husky install

.PHONY: pre-commit
pre-commit: ## Run pre-commit checks
	@echo "$(GREEN)Running pre-commit checks...$(NC)"
	@$(MAKE) lint-php
	@$(MAKE) phpstan
	@$(MAKE) test-unit

## Environment Setup
.PHONY: env-copy
env-copy: ## Copy .env.example to .env
	@echo "$(GREEN)Copying .env.example to .env...$(NC)"
	cp .env.example .env
	@echo "$(GREEN)Environment file created!$(NC)"

.PHONY: key-generate
key-generate: ## Generate application key
	@echo "$(GREEN)Generating application key...$(NC)"
	$(ARTISAN) key:generate

.PHONY: setup
setup: ## Complete setup for new installation
	@echo "$(GREEN)Setting up Laravel Homeschool Learning App...$(NC)"
	@$(MAKE) env-copy
	@$(MAKE) key-generate
	@$(MAKE) install
	@$(MAKE) migrate
	@$(MAKE) seed
	@$(MAKE) storage-link
	@echo "$(GREEN)✅ Setup complete! Run 'make dev' to start development server.$(NC)"

## Supabase (Local Development)
.PHONY: supabase-start
supabase-start: ## Start local Supabase instance
	@echo "$(GREEN)Starting local Supabase...$(NC)"
	supabase start

.PHONY: supabase-stop
supabase-stop: ## Stop local Supabase instance
	@echo "$(YELLOW)Stopping local Supabase...$(NC)"
	supabase stop

.PHONY: supabase-status
supabase-status: ## Check Supabase status
	@echo "$(GREEN)Checking Supabase status...$(NC)"
	supabase status

.PHONY: supabase-reset
supabase-reset: ## Reset Supabase database
	@echo "$(YELLOW)⚠️  This will reset the Supabase database! Press Ctrl+C to cancel...$(NC)"
	@sleep 3
	supabase db reset

## Quick Commands
.PHONY: fresh
fresh: ## Fresh install (reset everything and start clean)
	@echo "$(YELLOW)⚠️  This will delete all data and reinstall! Press Ctrl+C to cancel...$(NC)"
	@sleep 3
	@$(MAKE) cache-clear
	@$(MAKE) db-fresh
	@$(MAKE) optimize
	@echo "$(GREEN)✅ Fresh install complete!$(NC)"

.PHONY: update
update: ## Update dependencies (Composer + NPM)
	@echo "$(GREEN)Updating dependencies...$(NC)"
	$(COMPOSER) update
	$(NPM) update
	$(NPM) run build
	@echo "$(GREEN)Dependencies updated!$(NC)"

.PHONY: check
check: ## Run all checks (lint, phpstan, tests)
	@echo "$(GREEN)Running all checks...$(NC)"
	@$(MAKE) lint
	@$(MAKE) phpstan
	@$(MAKE) type-check
	@$(MAKE) test-unit
	@echo "$(GREEN)✅ All checks passed!$(NC)"

.PHONY: clean
clean: ## Clean build artifacts and caches
	@echo "$(YELLOW)Cleaning build artifacts and caches...$(NC)"
	rm -rf node_modules
	rm -rf vendor
	rm -rf public/build
	rm -rf public/hot
	rm -rf storage/framework/cache/*
	rm -rf storage/framework/sessions/*
	rm -rf storage/framework/views/*
	rm -rf bootstrap/cache/*
	@echo "$(GREEN)Cleaned!$(NC)"

## Production Commands
.PHONY: deploy
deploy: ## Deploy to production (run build and optimization)
	@echo "$(GREEN)Preparing for production deployment...$(NC)"
	@$(MAKE) build
	@$(MAKE) optimize
	@echo "$(GREEN)✅ Ready for deployment!$(NC)"

.PHONY: maintenance-on
maintenance-on: ## Enable maintenance mode
	@echo "$(YELLOW)Enabling maintenance mode...$(NC)"
	$(ARTISAN) down

.PHONY: maintenance-off
maintenance-off: ## Disable maintenance mode
	@echo "$(GREEN)Disabling maintenance mode...$(NC)"
	$(ARTISAN) up

# Aliases for common commands
.PHONY: s serve start
s: dev
serve: dev
start: dev

.PHONY: t
t: test

.PHONY: m
m: migrate

.PHONY: f
f: fresh