# Core Architecture Rules

These rules ALWAYS apply to the entire project.

## Framework Versions (IMPORTANT)

| Stack | Version | Docs |
|-------|---------|------|
| PHP | 8.4+ | https://www.php.net/docs.php |
| Laravel | 12.x | https://laravel.com/docs/12.x |
| FilamentPHP | 4.x | https://filamentphp.com/docs/4.x |
| Livewire | 3.x | https://livewire.laravel.com/docs |
| Pest | 3.x | https://pestphp.com/docs |
| Multitenancy | 4.x | https://spatie.be/docs/laravel-multitenancy/v4 |

**ALWAYS use documentation links above when implementing features.**

## DRY Principle (CRITICAL)

**Don't Repeat Yourself** - Este principio es FUNDAMENTAL en todo el proyecto.

### En el Código PHP
- **Traits**: Reutilizar comportamientos comunes (UserActions, LogChanges)
- **Base Classes**: Clases abstractas para lógica compartida
- **Actions**: Una acción, un propósito, reutilizable
- **DTOs**: Un DTO puede servir múltiples casos de uso similares
- **Services**: Extraer lógica repetida a servicios dedicados

### En Tests Pest (PHP)
- **Datasets**: Reutilizar datos de prueba con `dataset()`
- **Traits**: Compartir helpers entre tests
- **beforeEach**: Setup común por describe block

### En Tests Playwright (E2E)
- **Page Objects**: Una clase por página, reutilizable
- **Flows**: Flujos de negocio componibles (AuthFlow, ClientFlow)
- **Fixtures**: Estado de autenticación reutilizable
- **Helpers**: Funciones utilitarias compartidas

### Señales de Violación DRY
- Copy-paste de más de 3 líneas → Extraer a función/método
- Mismo selector en múltiples tests → Page Object
- Login repetido en tests → Fixture de autenticación
- Misma validación en múltiples lugares → Form Request / DTO

## Multi-tenancy Strategy

- **Identification**: Subdomain-based (`tenant.larasuite.test`)
- **Isolation**: Separate PostgreSQL database per tenant
- **Central DB**: `larasuite` (tenants, superadmin, plans)
- **Tenant DBs**: `larasuite_tenant_{id}`

## DDD Modular Architecture

Each module is a composer package in `/packages`:

```
packages/module-name/
├── src/
│   ├── Domain/           # Framework-agnostic business logic
│   │   ├── Entities/     # Domain entities (NO Eloquent)
│   │   ├── ValueObjects/ # Immutable value types
│   │   ├── Repositories/ # Repository INTERFACES only
│   │   └── Events/       # Domain events
│   ├── Application/      # Use cases (CQRS)
│   │   ├── Commands/     # Write operations
│   │   ├── Queries/      # Read operations
│   │   └── DTOs/         # Data transfer objects
│   └── Infrastructure/   # Framework implementations
│       ├── Persistence/  # Eloquent repos & models
│       └── Filament/     # Resources, pages
├── database/migrations/
└── tests/
```

## Layer Rules

1. **Domain layer**: NO Laravel imports, NO Eloquent, pure PHP
2. **Application layer**: Orchestrates domain, can use DTOs
3. **Infrastructure layer**: Laravel, Eloquent, Filament implementations

## Key Patterns

- **Repository Pattern**: Interface in Domain, implementation in Infrastructure
- **CQRS**: Commands for writes, Queries for reads
- **DTOs**: Immutable `readonly` classes for data transfer
- **Domain Events**: Decouple side effects from core logic
- **Actions**: Single-responsibility classes for business operations

## Module Communication

- Via **Domain Events** (preferred)
- Via **Application Services** with Anti-Corruption Layer
- NEVER direct cross-module Eloquent queries

## Filament Panels

- **Central Panel** (`/central`): Superadmin on main domain
- **App Panel** (`/`): Tenant application on subdomains
