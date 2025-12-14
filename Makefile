# =============================================================================
# LARASUITE - Development Commands
# =============================================================================

.PHONY: help build up down restart logs shell db-shell redis-shell \
        install migrate fresh seed test coverage analyze format \
        horizon queue clear cache optimize init

# Default target
.DEFAULT_GOAL := help

# Colors
YELLOW := \033[1;33m
GREEN := \033[1;32m
NC := \033[0m

# =============================================================================
# DOCKER COMMANDS
# =============================================================================

help: ## Show this help
	@echo "$(GREEN)Larasuite Development Commands$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'

build: ## Build Docker containers
	docker compose build

up: ## Start Docker containers
	docker compose up -d

up-dev: ## Start Docker containers with Vite dev server
	docker compose --profile dev up -d

down: ## Stop Docker containers
	docker compose down

restart: down up ## Restart Docker containers

logs: ## View Docker logs
	docker compose logs -f

logs-php: ## View PHP container logs
	docker compose logs -f php

logs-horizon: ## View Horizon container logs
	docker compose logs -f horizon

# =============================================================================
# SHELL ACCESS
# =============================================================================

shell: ## Access PHP container shell
	docker compose exec php sh

shell-root: ## Access PHP container shell as root
	docker compose exec -u root php sh

db-shell: ## Access PostgreSQL shell
	docker compose exec postgres psql -U larasuite -d larasuite

redis-shell: ## Access Redis CLI
	docker compose exec redis redis-cli

# =============================================================================
# LARAVEL COMMANDS
# =============================================================================

install: ## Install dependencies and setup
	docker compose exec php composer install
	docker compose exec php php artisan key:generate
	docker compose exec php php artisan storage:link
	docker compose exec php npm install

migrate: ## Run database migrations
	docker compose exec php php artisan migrate

migrate-tenant: ## Run tenant database migrations
	docker compose exec php php artisan tenants:migrate

fresh: ## Fresh migration with seeds
	docker compose exec php php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker compose exec php php artisan db:seed

# =============================================================================
# QUALITY & TESTING
# =============================================================================

test: ## Run tests
	docker compose exec php php artisan test

test-parallel: ## Run tests in parallel
	docker compose exec php php artisan test --parallel

coverage: ## Run tests with coverage
	docker compose exec php php artisan test --coverage --min=80

analyze: ## Run PHPStan analysis
	docker compose exec php ./vendor/bin/phpstan analyse

format: ## Format code with Pint
	docker compose exec php ./vendor/bin/pint

format-check: ## Check code formatting
	docker compose exec php ./vendor/bin/pint --test

rector: ## Run Rector refactoring
	docker compose exec php ./vendor/bin/rector process

rector-dry: ## Run Rector in dry-run mode
	docker compose exec php ./vendor/bin/rector process --dry-run

quality: format analyze test ## Run all quality checks

# =============================================================================
# QUEUE & CACHE
# =============================================================================

horizon: ## Start Horizon in foreground
	docker compose exec php php artisan horizon

queue: ## Process queue jobs
	docker compose exec php php artisan queue:work

clear: ## Clear all caches
	docker compose exec php php artisan optimize:clear

cache: ## Cache configuration
	docker compose exec php php artisan optimize

# =============================================================================
# UTILITIES
# =============================================================================

artisan: ## Run artisan command (usage: make artisan cmd="migrate:status")
	docker compose exec php php artisan $(cmd)

composer: ## Run composer command (usage: make composer cmd="require package")
	docker compose exec php composer $(cmd)

npm: ## Run npm command (usage: make npm cmd="install")
	docker compose exec php npm $(cmd)

tinker: ## Start Tinker REPL
	docker compose exec php php artisan tinker

routes: ## List all routes
	docker compose exec php php artisan route:list

# =============================================================================
# SETUP
# =============================================================================

setup: ## Initial project setup
	@echo "$(GREEN)Setting up Larasuite...$(NC)"
	cp -n .env.example .env || true
	$(MAKE) build
	$(MAKE) up
	@echo "Waiting for containers to be ready..."
	sleep 10
	$(MAKE) install
	$(MAKE) migrate
	@echo "$(GREEN)Setup complete! Visit http://larasuite.test$(NC)"

reset: ## Reset everything (DANGER: destroys data)
	docker compose down -v
	docker compose build --no-cache
	$(MAKE) up
	sleep 10
	$(MAKE) install
	$(MAKE) fresh

# =============================================================================
# NEW PROJECT INITIALIZATION
# =============================================================================

# Project name for initialization (default: larasuite)
PROJECT_NAME ?= larasuite
ADMIN_EMAIL ?= admin@$(PROJECT_NAME).test
ADMIN_PASSWORD ?= password

init: ## Initialize new project from template (usage: make init PROJECT_NAME=myapp)
	@echo "$(GREEN)Initializing new project: $(PROJECT_NAME)$(NC)"
	@echo ""
	@# 1. Copy environment file
	@echo "$(YELLOW)1/8 Creating .env file...$(NC)"
	@cp -n .env.example .env || true
	@# 2. Replace project name in files
	@echo "$(YELLOW)2/8 Configuring project name...$(NC)"
	@sed -i 's/larasuite/$(PROJECT_NAME)/g' .env
	@sed -i 's/larasuite/$(PROJECT_NAME)/g' docker-compose.yml
	@sed -i 's/larasuite\.test/$(PROJECT_NAME).test/g' .env
	@sed -i 's/larasuite\.test/$(PROJECT_NAME).test/g' playwright.config.ts
	@# 3. Build and start containers
	@echo "$(YELLOW)3/8 Building Docker containers...$(NC)"
	@docker compose build
	@echo "$(YELLOW)4/8 Starting containers...$(NC)"
	@docker compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 15
	@# 4. Fix permissions and install dependencies
	@echo "$(YELLOW)5/8 Installing PHP dependencies...$(NC)"
	@docker exec -u root $(PROJECT_NAME)-php sh -c "chown -R larasuite:larasuite /var/www/html/vendor" 2>/dev/null || true
	@docker compose exec php composer install
	@# 5. Generate application key
	@echo "$(YELLOW)6/8 Generating application key...$(NC)"
	@docker compose exec php php artisan key:generate
	@# 6. Run migrations
	@echo "$(YELLOW)7/8 Running database migrations...$(NC)"
	@docker compose exec php php artisan migrate --force
	@docker compose exec php php artisan migrate --path=database/migrations/landlord --force
	@# 7. Create admin user
	@echo "$(YELLOW)8/8 Creating admin user...$(NC)"
	@docker compose exec php php artisan tinker --execute="App\\Models\\User::create(['name'=>'Admin','email'=>'$(ADMIN_EMAIL)','password'=>bcrypt('$(ADMIN_PASSWORD)')])"
	@# Done
	@echo ""
	@echo "$(GREEN)=============================================$(NC)"
	@echo "$(GREEN)Project $(PROJECT_NAME) initialized!$(NC)"
	@echo "$(GREEN)=============================================$(NC)"
	@echo ""
	@echo "URL:      http://$(PROJECT_NAME).test"
	@echo "Email:    $(ADMIN_EMAIL)"
	@echo "Password: $(ADMIN_PASSWORD)"
	@echo ""
	@echo "$(YELLOW)Next steps:$(NC)"
	@echo "1. Add '127.0.0.1 $(PROJECT_NAME).test' to your hosts file"
	@echo "2. Visit http://$(PROJECT_NAME).test/central/login"
	@echo ""

init-git: ## Reinitialize git for new project
	@echo "$(YELLOW)Reinitializing git repository...$(NC)"
	@rm -rf .git
	@git init
	@git add .
	@git commit -m "Initial commit: $(PROJECT_NAME) from Larasuite template"
	@echo "$(GREEN)Git reinitialized with fresh history$(NC)"
