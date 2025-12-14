---
paths: "app/**/*.php, routes/**/*.php, packages/**/Infrastructure/**/*.php"
description: Laravel framework patterns for app code and package infrastructure
---
# Laravel Patterns

> **Laravel 12.x** - Docs: https://laravel.com/docs/12.x

## Controllers

Keep thin - validate, call action, return response:

```php
final class ClientController extends Controller
{
    public function store(
        StoreClientRequest $request,
        CreateClientAction $action,
    ): JsonResponse {
        $client = $action->execute($request->validated());
        return new JsonResponse(new ClientResource($client), 201);
    }
}
```

## Action Pattern

Single-responsibility classes for business operations:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\DTOs\ClientDTO;
use App\Models\Client;

final class CreateClientAction
{
    public function __construct(
        private readonly ClientRepository $repository,
        private readonly EventDispatcher $events,
    ) {}

    public function execute(array $data): ClientDTO
    {
        $client = $this->repository->create($data);

        $this->events->dispatch(new ClientCreated($client));

        return ClientDTO::fromModel($client);
    }
}
```

### Action Best Practices

- **Single method**: Use `execute()` or `handle()` consistently
- **Dependency injection**: Inject services via constructor
- **Return DTOs**: Never return Eloquent models from actions
- **Dispatch events**: Decouple side effects from core logic
- **Type hints**: Full parameter and return type declarations

### Base Action Class (Optional)

```php
abstract class BaseAction
{
    protected function transaction(\Closure $callback): mixed
    {
        return DB::transaction($callback);
    }

    protected function authorize(string $ability, mixed $model): void
    {
        if (Gate::denies($ability, $model)) {
            throw new AuthorizationException();
        }
    }
}
```

## Form Requests (ALWAYS use)

```php
final class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Client::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('clients')],
        ];
    }
}
```

## Eloquent

```php
// Enable strict mode (already in AppServiceProvider)
Model::shouldBeStrict(!app()->isProduction());

// ALWAYS eager load to prevent N+1
$clients = Client::with(['invoices', 'contacts'])->get();

// Use $fillable, NEVER $guarded = []
protected $fillable = ['name', 'email'];
```

## Configuration

```php
// GOOD - use config()
$timeout = config('services.api.timeout');

// BAD - env() only in config files
$timeout = env('API_TIMEOUT');
```

## Queued Jobs

```php
final class ProcessInvoice implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}

    public function handle(PdfGenerator $generator): void
    {
        $generator->generate($this->invoice);
    }
}
```

## Security Checklist

- [ ] CSRF on all forms (`@csrf`)
- [ ] Mass assignment protection (`$fillable`)
- [ ] Authorization checks (`$this->authorize()`)
- [ ] Input validation (Form Requests)
- [ ] File uploads validated (mimes, max size)
