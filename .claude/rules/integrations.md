---
paths: "**/Services/**/*Integration*.php, **/Services/**/*Client*.php, **/Infrastructure/**/Services/**/*.php"
description: External service integration patterns
---
# Integration Patterns

## Base HTTP Integration Action

```php
<?php

declare(strict_types=1);

namespace App\Services\Integration;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseHttpIntegration
{
    protected int $timeout = 30;
    protected int $retries = 3;
    protected int $retryDelay = 100; // ms
    protected array $retryOn = [500, 502, 503, 504];

    abstract protected function baseUrl(): string;

    abstract protected function defaultHeaders(): array;

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withHeaders($this->defaultHeaders())
            ->timeout($this->timeout)
            ->retry(
                times: $this->retries,
                sleepMilliseconds: $this->retryDelay,
                when: fn (Response $response) => in_array($response->status(), $this->retryOn),
            )
            ->withOptions([
                'verify' => app()->isProduction(),
            ]);
    }

    protected function handleResponse(Response $response, string $operation): array
    {
        if ($response->failed()) {
            $this->logFailure($operation, $response);
            throw new IntegrationException(
                message: "Integration failed: {$operation}",
                code: $response->status(),
                context: [
                    'operation' => $operation,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ],
            );
        }

        $this->logSuccess($operation, $response);

        return $response->json();
    }

    protected function logFailure(string $operation, Response $response): void
    {
        Log::error("Integration failed: {$operation}", [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
        ]);
    }

    protected function logSuccess(string $operation, Response $response): void
    {
        Log::debug("Integration success: {$operation}", [
            'status' => $response->status(),
        ]);
    }
}
```

## Verifactu Integration Example

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Infrastructure\Services;

use App\Services\Integration\BaseHttpIntegration;
use Larasuite\Invoicing\Domain\Entities\Invoice;

final class VerifactuIntegration extends BaseHttpIntegration
{
    protected int $timeout = 60;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $environment,
    ) {}

    protected function baseUrl(): string
    {
        return match ($this->environment) {
            'production' => 'https://api.verifactu.es/v1',
            default => 'https://sandbox.verifactu.es/v1',
        };
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function submitInvoice(Invoice $invoice): VerifactuResponse
    {
        $response = $this->client()
            ->post('/invoices', $this->mapInvoiceToPayload($invoice));

        $data = $this->handleResponse($response, 'submitInvoice');

        return VerifactuResponse::fromArray($data);
    }

    public function getInvoiceStatus(string $verifactuId): VerifactuStatus
    {
        $response = $this->client()
            ->get("/invoices/{$verifactuId}/status");

        $data = $this->handleResponse($response, 'getInvoiceStatus');

        return VerifactuStatus::from($data['status']);
    }

    private function mapInvoiceToPayload(Invoice $invoice): array
    {
        return [
            'number' => $invoice->number()->value,
            'issue_date' => $invoice->issuedAt()->format('Y-m-d'),
            'client' => [
                'nif' => $invoice->client()->nif(),
                'name' => $invoice->client()->name(),
            ],
            'lines' => $invoice->lines()->map(fn ($line) => [
                'description' => $line->description(),
                'quantity' => $line->quantity(),
                'unit_price' => $line->unitPrice()->cents / 100,
                'vat_rate' => $line->vatRate(),
            ])->all(),
            'total' => $invoice->total()->cents / 100,
        ];
    }
}
```

## Service Provider Registration

```php
// In package service provider
public function register(): void
{
    $this->app->singleton(VerifactuIntegration::class, function ($app) {
        return new VerifactuIntegration(
            apiKey: config('services.verifactu.api_key'),
            environment: config('services.verifactu.environment'),
        );
    });
}
```

## Integration Response DTOs

```php
<?php

declare(strict_types=1);

namespace Larasuite\Invoicing\Infrastructure\Services;

final readonly class VerifactuResponse
{
    public function __construct(
        public string $id,
        public string $status,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            status: $data['status'],
            errorCode: $data['error_code'] ?? null,
            errorMessage: $data['error_message'] ?? null,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'accepted';
    }
}
```

## Circuit Breaker Pattern

```php
<?php

declare(strict_types=1);

namespace App\Services\Integration;

use Illuminate\Support\Facades\Cache;

trait WithCircuitBreaker
{
    protected int $failureThreshold = 5;
    protected int $recoveryTimeout = 60; // seconds

    protected function isCircuitOpen(): bool
    {
        $key = $this->circuitBreakerKey();
        $failures = Cache::get("{$key}:failures", 0);

        if ($failures >= $this->failureThreshold) {
            $openedAt = Cache::get("{$key}:opened_at");

            if ($openedAt && now()->diffInSeconds($openedAt) < $this->recoveryTimeout) {
                return true;
            }

            // Reset after recovery timeout
            $this->resetCircuit();
        }

        return false;
    }

    protected function recordFailure(): void
    {
        $key = $this->circuitBreakerKey();
        $failures = Cache::increment("{$key}:failures");

        if ($failures >= $this->failureThreshold) {
            Cache::put("{$key}:opened_at", now(), $this->recoveryTimeout * 2);
        }
    }

    protected function resetCircuit(): void
    {
        $key = $this->circuitBreakerKey();
        Cache::forget("{$key}:failures");
        Cache::forget("{$key}:opened_at");
    }

    protected function circuitBreakerKey(): string
    {
        return 'circuit:' . class_basename($this);
    }
}
```

## Testing Integrations

```php
use Illuminate\Support\Facades\Http;

it('submits invoice to Verifactu', function () {
    Http::fake([
        'sandbox.verifactu.es/*' => Http::response([
            'id' => 'VF-123456',
            'status' => 'accepted',
        ], 200),
    ]);

    $integration = app(VerifactuIntegration::class);
    $response = $integration->submitInvoice($invoice);

    expect($response->isSuccess())->toBeTrue();
    expect($response->id)->toBe('VF-123456');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sandbox.verifactu.es/v1/invoices';
    });
});

it('handles Verifactu failure', function () {
    Http::fake([
        'sandbox.verifactu.es/*' => Http::response([
            'error_code' => 'INVALID_NIF',
            'error_message' => 'Client NIF is invalid',
        ], 422),
    ]);

    $integration = app(VerifactuIntegration::class);

    expect(fn () => $integration->submitInvoice($invoice))
        ->toThrow(IntegrationException::class);
});
```

## Best Practices

1. **Abstract base class**: Share retry, timeout, logging logic
2. **Environment-aware**: Different URLs for sandbox/production
3. **Typed responses**: Use DTOs for integration responses
4. **Circuit breaker**: Prevent cascading failures
5. **Comprehensive logging**: Log requests and responses
6. **Retry with backoff**: Retry transient failures
7. **Test with fakes**: Use `Http::fake()` in tests
8. **Config-driven**: Store credentials in config, not code
