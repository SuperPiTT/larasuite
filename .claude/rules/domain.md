---
paths: "packages/**/Domain/**/*.php"
description: DDD domain layer rules - framework agnostic
---
# Domain Layer Rules

**CRITICAL**: Domain layer MUST be framework-agnostic. NO Laravel, NO Eloquent.

## Entities

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Domain\Entities;

final class Invoice
{
    /** @var array<DomainEvent> */
    private array $events = [];

    public function __construct(
        private readonly InvoiceId $id,
        private readonly ClientId $clientId,
        private InvoiceNumber $number,
        private Money $total,
        private InvoiceStatus $status,
    ) {}

    public function markAsPaid(Money $amount): void
    {
        if (!$this->status->isPending()) {
            throw InvoiceException::cannotPayNonPending($this->id);
        }

        $this->status = InvoiceStatus::Paid;
        $this->recordEvent(new InvoicePaid($this->id, $amount));
    }

    /** @return array<DomainEvent> */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}
```

## Value Objects

Immutable, self-validating:

```php
final readonly class Money
{
    public function __construct(
        public int $cents,
        public string $currency,
    ) {
        if ($cents < 0) {
            throw InvalidMoneyException::negative($cents);
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw InvalidMoneyException::currencyMismatch();
        }
        return new self($this->cents + $other->cents, $this->currency);
    }
}
```

## Repository Interfaces

```php
interface InvoiceRepositoryInterface
{
    public function find(InvoiceId $id): ?Invoice;
    public function save(Invoice $invoice): void;
    public function nextIdentity(): InvoiceId;
}
```

## Domain Events

```php
final readonly class InvoicePaid implements DomainEvent
{
    public function __construct(
        public InvoiceId $invoiceId,
        public Money $amount,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
```

## Forbidden in Domain

- `use Illuminate\*`
- `use App\*`
- Eloquent models
- HTTP concerns
- Database queries
- External service calls
