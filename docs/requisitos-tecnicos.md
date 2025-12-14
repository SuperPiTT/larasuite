# Arquitectura Laravel Enterprise

## Principios no negociables

- El Dominio no conoce Laravel
- Eloquent NO entra al Dominio
- Eloquent solo en Infrastructure
- Todo acceso a datos pasa por Repositories
- Controllers solo orquestan
- Nada de lógica en Models
- Tipos estrictos siempre
- Usamos DDD (domain driven design), SOLID y TDD (test driven development), clean code

## Uso de Eloquent (REGLAS ESTRICTAS)
### ✅ Permitido
- Queries
- Relaciones
- Scopes simples (sin lógica de negocio)
- Casting
- Transactions (solo Infrastructure)
### ❌ Prohibido
- Lógica de negocio
- Validaciones complejas
- Estados del dominio
- Reglas de negocio
- Eventos del dominio

## Reglas de disciplina (no negociables)

- declare(strict_types=1); SIEMPRE
- PHPStan nivel máximo
- No Facades en Domain/Application
- No helpers globales
- DTOs obligatorios
- Value Objects para primitives importantes
- No lógica en Models
- Todo por interface
- Tests obligatorios