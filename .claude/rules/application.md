---
paths: "packages/**/Application/**/*.php"
description: DDD application layer - CQRS patterns
---
# Application Layer Rules

Application layer orchestrates domain logic via Commands and Queries (CQRS).

## Commands (Write Operations)

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Application\Commands;

final readonly class CreateInvoiceCommand
{
    public function __construct(
        public string $clientId,
        public array $items,
        public string $dueDate,
    ) {}
}
```

## Command Handlers

```php
final class CreateInvoiceHandler
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly InvoiceNumberGenerator $generator,
        private readonly EventDispatcherInterface $events,
    ) {}

    public function handle(CreateInvoiceCommand $command): InvoiceDTO
    {
        $invoice = Invoice::create(
            id: $this->invoices->nextIdentity(),
            clientId: new ClientId($command->clientId),
            number: $this->generator->generate(),
            items: $command->items,
            dueAt: new \DateTimeImmutable($command->dueDate),
        );

        $this->invoices->save($invoice);

        foreach ($invoice->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }

        return InvoiceDTO::fromEntity($invoice);
    }
}
```

## Queries (Read Operations)

```php
final readonly class GetClientInvoicesQuery
{
    public function __construct(
        public string $clientId,
        public ?string $status = null,
        public int $page = 1,
    ) {}
}
```

## Query Handlers

Can use Eloquent directly (optimized for reads):

```php
final class GetClientInvoicesHandler
{
    public function __construct(
        private readonly InvoiceQueryRepository $repository,
    ) {}

    public function handle(GetClientInvoicesQuery $query): InvoiceListDTO
    {
        return $this->repository->findByClientPaginated(
            $query->clientId,
            $query->status,
            $query->page,
        );
    }
}
```

## DTOs

```php
final readonly class InvoiceDTO
{
    public function __construct(
        public string $id,
        public string $number,
        public int $totalCents,
        public string $status,
    ) {}

    public static function fromEntity(Invoice $invoice): self
    {
        return new self(
            id: $invoice->id()->value,
            number: $invoice->number()->value,
            totalCents: $invoice->total()->cents,
            status: $invoice->status()->value,
        );
    }
}
```

## Dependencies

- **Can import**: Domain layer classes
- **Cannot import**: Infrastructure (Eloquent, Laravel)
- **Interfaces**: Defined here, implemented in Infrastructure
