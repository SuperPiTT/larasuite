---
paths: "**/*.php"
description: PHP code standards for all PHP files
---
# PHP Standards

> **PHP 8.4+** - Docs: https://www.php.net/docs.php

## Required in ALL PHP files

```php
<?php

declare(strict_types=1);
```

## Class Rules

- Use `final` by default (remove only when inheritance needed)
- Use `readonly` for immutable properties
- Constructor property promotion required
- PHPStan level 9 compliance mandatory

```php
final class Example
{
    public function __construct(
        private readonly string $name,
        private readonly int $value,
    ) {}
}
```

## Type Hints

- ALL parameters MUST have type hints
- ALL return types MUST be declared
- Use `?Type` or `Type|null` for nullable
- Avoid `mixed` unless truly necessary

## Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `UserService` |
| Methods | camelCase | `getUserById()` |
| Variables | camelCase | `$userName` |
| Constants | SCREAMING_SNAKE | `MAX_RETRIES` |

## PHPDoc (only when needed)

```php
/** @return Collection<int, User> */
public function getUsers(): Collection

/** @param array{name: string, email: string} $data */
public function create(array $data): User
```

## Forbidden

- `@var` when type hints suffice
- `eval()`, `extract()`
- `array` without generics (use `array<string, int>`)
- Suppressing errors with `@`

## Pint Configuration

Recommended strict rules in `pint.json`:

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "final_class": true,
        "global_namespace_import": {
            "import_classes": true,
            "import_constants": true,
            "import_functions": true
        },
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "destruct",
                "magic",
                "method_public",
                "method_protected",
                "method_private"
            ]
        },
        "trailing_comma_in_multiline": {
            "elements": ["arrays", "arguments", "parameters"]
        },
        "single_line_empty_body": true,
        "no_unused_imports": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        }
    }
}
```

Run with: `composer format` or `./vendor/bin/pint`
