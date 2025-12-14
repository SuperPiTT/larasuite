# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Larasuite** is a B2B SaaS platform for field service companies in Spain. It manages clients, field services, operators, vehicles, invoicing, and maintenance contracts with full Verifactu compliance.

## Tech Stack & Documentation

### Core Framework Versions

| Technology | Version | Documentation |
|------------|---------|---------------|
| **PHP** | 8.4+ | https://www.php.net/docs.php |
| **Laravel** | 12.x | https://laravel.com/docs/12.x |
| **FilamentPHP** | 4.x | https://filamentphp.com/docs/4.x |
| **Livewire** | 3.x | https://livewire.laravel.com/docs |
| **Alpine.js** | 3.x | https://alpinejs.dev/docs |
| **Tailwind CSS** | 4.x | https://tailwindcss.com/docs |

### Infrastructure & Tools

| Technology | Version | Documentation |
|------------|---------|---------------|
| **PostgreSQL** | 16+ | https://www.postgresql.org/docs/16/ |
| **Redis** | 7.x | https://redis.io/docs/ |
| **Laravel Horizon** | 5.x | https://laravel.com/docs/12.x/horizon |
| **Pest** | 3.x | https://pestphp.com/docs |
| **Playwright** | latest | https://playwright.dev/docs/intro |
| **spatie/laravel-multitenancy** | 4.x | https://spatie.be/docs/laravel-multitenancy/v4 |

### Quick Reference

- **Multi-tenancy**: spatie/laravel-multitenancy (separate database per tenant)
- **Database**: PostgreSQL (central + tenant DBs)
- **Cache/Queue**: Redis 7 + Laravel Horizon
- **Infrastructure**: Docker

## Architecture

### Multi-tenancy Strategy
- **Identification**: Subdomain-based (`tenant.larasuite.test`)
- **Isolation**: Separate database per tenant
- **Central DB**: `larasuite` (tenants table, superadmin users)
- **Tenant DBs**: `larasuite_tenant_{id}`

### Filament Panels
- **Central Panel** (`/central`): Superadmin management on main domain
- **App Panel** (`/`): Tenant application on subdomains

### Modular Architecture (DDD Pragmatic)

Each module is a composer package in `/packages`:

```
packages/module-name/
├── src/
│   ├── Domain/           # Business logic (framework-agnostic)
│   │   ├── Entities/     # Domain entities
│   │   ├── ValueObjects/ # Immutable value types
│   │   ├── Repositories/ # Repository interfaces
│   │   ├── Events/       # Domain events
│   │   └── Exceptions/   # Domain exceptions
│   ├── Application/      # Use cases
│   │   ├── Commands/     # Write operations (CQRS)
│   │   ├── Queries/      # Read operations (CQRS)
│   │   └── DTOs/         # Data Transfer Objects
│   └── Infrastructure/   # Framework implementations
│       ├── Persistence/  # Eloquent repositories
│       ├── Providers/    # Service providers
│       ├── Http/         # Controllers, requests
│       └── Filament/     # Resources, pages
├── config/
├── database/migrations/
├── routes/
└── tests/
```

## Coding Standards

### PHP Strict Types
- ALL files MUST have `declare(strict_types=1);`
- Use typed properties, parameters, and return types
- Prefer `final` classes unless inheritance is needed
- Use `readonly` for immutable properties

### Naming Conventions
- Classes: PascalCase (`UserRepository`)
- Methods/Variables: camelCase (`getUserById`)
- Constants: SCREAMING_SNAKE_CASE (`MAX_ATTEMPTS`)
- Database columns: snake_case (`created_at`)

### Quality Tools
- **PHPStan Level 9**: `composer analyze`
- **Rector**: `composer rector`
- **Pint**: `composer format`
- **Pest**: `composer test`

## Commands

```bash
# Docker
make up                 # Start containers
make down               # Stop containers
make shell              # Access PHP container
make logs               # View logs

# Laravel
make install            # Install dependencies
make migrate            # Run migrations
make fresh              # Fresh migrate with seeds

# Quality
make analyze            # PHPStan analysis
make format             # Code formatting
make test               # Run tests
make quality            # All quality checks

# Testing
npm run test:e2e        # Playwright E2E tests
```

## Development Guidelines

1. **Always** run quality checks before committing
2. **Never** commit code that fails PHPStan level 9
3. **Write tests** for all new features (TDD preferred)
4. **Domain logic** must be framework-agnostic
5. **Use DTOs** for data transfer between layers
6. **Repositories** abstract persistence layer
7. **Services** contain application logic, not controllers

## Important Files

- `docker-compose.yml` - Docker services configuration
- `phpstan.neon` - Static analysis config
- `rector.php` - Refactoring rules
- `pint.json` - Code style config
- `config/multitenancy.php` - Multi-tenant config
- `config/horizon.php` - Queue workers config
