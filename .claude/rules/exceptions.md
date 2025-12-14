---
paths: "**/Exceptions/**/*.php, **/Domain/**/Exceptions/**/*.php"
description: Custom exception patterns with factory methods
---
# Custom Exception Patterns

## Domain Exception Structure

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Domain\Exceptions;

use Larasuite\Invoicing\Domain\ValueObjects\InvoiceId;

final class InvoiceException extends \DomainException
{
    private function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function notFound(InvoiceId $id): self
    {
        return new self(
            message: "Invoice [{$id->value}] not found.",
            errorCode: 'INVOICE_NOT_FOUND',
            context: ['invoice_id' => $id->value],
        );
    }

    public static function cannotPayNonPending(InvoiceId $id): self
    {
        return new self(
            message: "Cannot mark invoice [{$id->value}] as paid. Only pending invoices can be paid.",
            errorCode: 'INVOICE_INVALID_STATUS_TRANSITION',
            context: ['invoice_id' => $id->value],
        );
    }

    public static function cannotModifyFinalizedInvoice(InvoiceId $id): self
    {
        return new self(
            message: "Cannot modify finalized invoice [{$id->value}].",
            errorCode: 'INVOICE_ALREADY_FINALIZED',
            context: ['invoice_id' => $id->value],
        );
    }

    public static function duplicateNumber(string $number): self
    {
        return new self(
            message: "Invoice number [{$number}] already exists.",
            errorCode: 'INVOICE_DUPLICATE_NUMBER',
            context: ['invoice_number' => $number],
        );
    }
}
```

## Value Object Exception

```php
<?php

declare(strict_types=1);

namespace Larasuite\Shared\Domain\Exceptions;

final class InvalidMoneyException extends \InvalidArgumentException
{
    public static function negative(int $cents): self
    {
        return new self("Money amount cannot be negative. Got: {$cents} cents.");
    }

    public static function currencyMismatch(string $expected, string $actual): self
    {
        return new self("Currency mismatch. Expected [{$expected}], got [{$actual}].");
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency code: [{$currency}].");
    }
}
```

## Application Layer Exception

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Application\Exceptions;

final class InvoiceCommandException extends \RuntimeException
{
    public static function clientNotFound(string $clientId): self
    {
        return new self("Client [{$clientId}] not found when creating invoice.");
    }

    public static function insufficientPermissions(string $action): self
    {
        return new self("Insufficient permissions to {$action}.");
    }
}
```

## Infrastructure Exception (with HTTP codes)

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Infrastructure\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class InvoiceApiException extends HttpException
{
    public static function notFound(string $id): self
    {
        return new self(404, "Invoice not found: {$id}");
    }

    public static function forbidden(): self
    {
        return new self(403, 'You do not have permission to access this invoice.');
    }

    public static function unprocessable(string $reason): self
    {
        return new self(422, $reason);
    }
}
```

## Exception Handler Integration

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (InvoiceException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->errorCode,
                'context' => $e->context,
            ], 422);
        }

        return back()->withErrors(['invoice' => $e->getMessage()]);
    });
})
```

## Best Practices

1. **Static factory methods**: Always use named constructors for clarity
2. **Error codes**: Include machine-readable codes for API consumers
3. **Context data**: Include relevant IDs/data for debugging
4. **Layer separation**: Domain exceptions don't know about HTTP codes
5. **Specificity**: Create specific exceptions, not generic ones

## Throwing Exceptions

```php
// Domain Entity
public function markAsPaid(Money $amount): void
{
    if (!$this->status->isPending()) {
        throw InvoiceException::cannotPayNonPending($this->id);
    }
    // ...
}

// Application Handler
public function handle(CreateInvoiceCommand $command): InvoiceDTO
{
    $client = $this->clients->find(new ClientId($command->clientId));

    if ($client === null) {
        throw InvoiceCommandException::clientNotFound($command->clientId);
    }
    // ...
}
```
