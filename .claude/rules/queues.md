---
paths: "**/Jobs/**/*.php, **/Listeners/**/*.php"
description: Queue optimization and job patterns
---
# Queue Patterns

> **Laravel Queues** - Docs: https://laravel.com/docs/12.x/queues
> **Laravel Horizon** - Docs: https://laravel.com/docs/12.x/horizon

## Job Structure

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Attributes\WithoutRelations;

final class ProcessInvoicePdf implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        #[WithoutRelations]
        public readonly Invoice $invoice,
    ) {}

    public function handle(PdfGenerator $generator): void
    {
        $generator->generate($this->invoice);
    }

    public function failed(\Throwable $exception): void
    {
        // Notify admin, log error, etc.
        logger()->error('Invoice PDF generation failed', [
            'invoice_id' => $this->invoice->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

## WithoutRelations Attribute (PHP 8.4+)

Prevents serialization of loaded relationships to reduce payload:

```php
public function __construct(
    #[WithoutRelations]
    public readonly Invoice $invoice,
    #[WithoutRelations]
    public readonly Client $client,
) {}
```

## Queue Configuration

```php
// Define queue priority in job
public function __construct(Invoice $invoice)
{
    $this->invoice = $invoice;
    $this->onQueue('invoices');
    $this->onConnection('redis');
}

// Or chain configuration
ProcessInvoicePdf::dispatch($invoice)
    ->onQueue('high')
    ->delay(now()->addMinutes(5));
```

## Horizon Queue Priority

```php
// config/horizon.php
'supervisor-1' => [
    'connection' => 'redis',
    'queue' => ['high', 'invoices', 'notifications', 'default', 'low'],
    'balance' => 'auto',
    'processes' => 10,
    'tries' => 3,
],
```

## Batch Processing

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

Bus::batch([
    new ProcessInvoicePdf($invoice1),
    new ProcessInvoicePdf($invoice2),
    new ProcessInvoicePdf($invoice3),
])
    ->then(fn (Batch $batch) => logger()->info('All invoices processed'))
    ->catch(fn (Batch $batch, \Throwable $e) => logger()->error('Batch failed'))
    ->finally(fn (Batch $batch) => logger()->info('Batch completed'))
    ->name('Invoice PDF Generation')
    ->onQueue('invoices')
    ->dispatch();
```

## Job Chaining

```php
Bus::chain([
    new CreateInvoice($data),
    new GenerateInvoicePdf($invoiceId),
    new SendInvoiceEmail($invoiceId),
])->dispatch();
```

## Rate Limiting

```php
use Illuminate\Queue\Middleware\RateLimited;

public function middleware(): array
{
    return [
        new RateLimited('invoices'),
    ];
}

// In AppServiceProvider
RateLimiter::for('invoices', function (object $job) {
    return Limit::perMinute(100);
});
```

## Unique Jobs

```php
use Illuminate\Contracts\Queue\ShouldBeUnique;

final class ProcessInvoicePdf implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600; // 1 hour

    public function uniqueId(): string
    {
        return (string) $this->invoice->id;
    }
}
```

## Monitoring with Horizon

```php
// Check queue health
Horizon::routeMailNotificationsTo('admin@larasuite.com');

// Custom metrics
Horizon::night(); // Dark theme

// Tags for filtering
public function tags(): array
{
    return [
        'invoice:' . $this->invoice->id,
        'tenant:' . $this->invoice->tenant_id,
    ];
}
```

## Event Listeners as Jobs

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoicePaid;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPaymentConfirmation implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(InvoicePaid $event): void
    {
        // Send notification
    }

    public function shouldQueue(InvoicePaid $event): bool
    {
        // Only queue if client wants notifications
        return $event->invoice->client->wants_notifications;
    }
}
```

## Best Practices

1. **Small payloads**: Use `#[WithoutRelations]` or pass only IDs
2. **Idempotent jobs**: Jobs should be safe to retry
3. **Appropriate timeouts**: Set realistic `$timeout` values
4. **Queue segregation**: Use different queues for different priorities
5. **Monitor with Horizon**: Use tags and metrics for observability
6. **Handle failures**: Always implement `failed()` method
7. **Test jobs**: Use `Queue::fake()` in tests
