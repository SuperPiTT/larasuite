---
paths: "**/Enums/**/*.php, **/Domain/**/Enums/**/*.php"
description: PHP Backed Enums with business logic patterns
---
# Enum Patterns

## Basic Structure

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Domain\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Pending => 'Pendiente',
            self::Paid => 'Pagada',
            self::Overdue => 'Vencida',
            self::Cancelled => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Cancelled => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Pending => 'heroicon-o-clock',
            self::Paid => 'heroicon-o-check-circle',
            self::Overdue => 'heroicon-o-exclamation-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }
}
```

## Business Logic in Enums

```php
enum InvoiceStatus: string
{
    // ... cases ...

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => in_array($status, [self::Pending, self::Cancelled]),
            self::Pending => in_array($status, [self::Paid, self::Overdue, self::Cancelled]),
            self::Overdue => in_array($status, [self::Paid, self::Cancelled]),
            self::Paid, self::Cancelled => false,
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled]);
    }
}
```

## Collection Methods

```php
enum InvoiceStatus: string
{
    // ... cases ...

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** @return array<self> */
    public static function active(): array
    {
        return [self::Pending, self::Overdue];
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

## Filament Integration

```php
// In Filament Resource
Tables\Columns\TextColumn::make('status')
    ->badge()
    ->formatStateUsing(fn (InvoiceStatus $state) => $state->label())
    ->color(fn (InvoiceStatus $state) => $state->color())
    ->icon(fn (InvoiceStatus $state) => $state->icon());

// Filter
Tables\Filters\SelectFilter::make('status')
    ->options(InvoiceStatus::options());

// Form
Forms\Components\Select::make('status')
    ->options(InvoiceStatus::options())
    ->default(InvoiceStatus::Draft->value);
```

## Validation Rule

```php
use Illuminate\Validation\Rules\Enum;

'status' => ['required', new Enum(InvoiceStatus::class)],
```

## Eloquent Casting

```php
final class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
        ];
    }
}
```

## Integer Backed Enums

Use for priority, order, or numeric states:

```php
enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Urgent = 4;

    public function isHigherThan(self $other): bool
    {
        return $this->value > $other->value;
    }
}
```
