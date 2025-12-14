# Skeleton Package Template

This is a template package demonstrating the DDD (Domain-Driven Design) pragmatic architecture used in Larasuite.

## Package Structure

```
packages/your-package/
├── composer.json
├── config/
│   └── your-package.php
├── database/
│   └── migrations/
├── routes/
│   └── web.php
├── src/
│   ├── Domain/                    # Core business logic (framework-agnostic)
│   │   ├── Entities/              # Domain entities with business rules
│   │   ├── ValueObjects/          # Immutable value types
│   │   ├── Repositories/          # Repository interfaces
│   │   ├── Events/                # Domain events
│   │   └── Exceptions/            # Domain-specific exceptions
│   │
│   ├── Application/               # Use cases and orchestration
│   │   ├── Commands/              # Write operations (CQRS)
│   │   ├── Queries/               # Read operations (CQRS)
│   │   └── DTOs/                  # Data Transfer Objects
│   │
│   └── Infrastructure/            # Framework-specific implementations
│       ├── Persistence/           # Repository implementations (Eloquent)
│       ├── Providers/             # Laravel service providers
│       ├── Http/
│       │   ├── Controllers/       # HTTP controllers
│       │   └── Requests/          # Form requests
│       └── Filament/
│           └── Resources/         # Filament admin resources
│
└── tests/
    ├── Unit/                      # Unit tests for domain logic
    └── Feature/                   # Integration tests
```

## Creating a New Package

1. Copy this skeleton:
   ```bash
   cp -r packages/.skeleton packages/your-package
   ```

2. Update `composer.json`:
   - Change `name` to `larasuite/your-package`
   - Update namespaces in autoload

3. Rename namespaces in all PHP files from `Skeleton` to `YourPackage`

4. Update the main `composer.json` to include your package:
   ```json
   {
       "require": {
           "larasuite/your-package": "@dev"
       }
   }
   ```

5. Run `composer update` to install the package

## Principles

### Domain Layer
- Framework-agnostic
- Contains business logic
- Uses value objects for type safety
- Entities are rich models with behavior

### Application Layer
- Orchestrates domain objects
- Implements use cases
- Uses CQRS pattern (Commands/Queries)
- DTOs for data transfer

### Infrastructure Layer
- Framework-specific code
- Repository implementations
- HTTP layer
- Filament resources

## Testing

```bash
cd packages/your-package
composer test
```
