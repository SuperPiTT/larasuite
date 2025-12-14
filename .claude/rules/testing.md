---
paths: "tests/**/*.php, **/tests/**/*.php"
description: Pest PHP testing standards
---
# Testing Standards (Pest)

> **Pest 3.x** - Docs: https://pestphp.com/docs
> **Laravel Testing** - Docs: https://laravel.com/docs/12.x/testing

## File Structure

```php
<?php

declare(strict_types=1);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('Client Creation', function (): void {
    it('creates client with valid data', function (): void {
        $this->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'Test Client',
                'email' => 'test@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['email' => 'test@example.com']);
    });

    it('validates required fields', function (): void {
        $this->actingAs($this->user)
            ->post(route('clients.store'), [])
            ->assertSessionHasErrors(['name', 'email']);
    });
});
```

## Naming

```php
// GOOD - Behavior focused
it('sends notification when invoice is overdue', function () {});
it('prevents duplicate client emails', function () {});

// BAD - Implementation focused
it('calls sendNotification method', function () {});
```

## Assertions

```php
// Expectations
expect($value)->toBe('exact');
expect($user)->toBeInstanceOf(User::class);
expect($items)->toHaveCount(3);
expect(fn () => $action())->toThrow(ValidationException::class);

// Database
$this->assertDatabaseHas('clients', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('clients', ['email' => 'deleted@example.com']);
```

## Livewire Testing

```php
Livewire::actingAs($this->user)
    ->test(CreateClient::class)
    ->set('name', 'Test')
    ->set('email', 'test@example.com')
    ->call('save')
    ->assertHasNoErrors()
    ->assertDispatched('client-created');
```

## Mocking

```php
Mail::fake();
Queue::fake();

// Action...

Mail::assertSent(WelcomeMail::class);
Queue::assertPushed(ProcessInvoice::class);
```

## Multi-tenancy

```php
beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});

afterEach(function (): void {
    Tenant::forgetCurrent();
});
```

## DRY: Reusable Test Patterns

### Datasets (Reutilizar datos de prueba)

```php
// tests/Datasets/Clients.php
dataset('valid_clients', [
    'individual' => ['name' => 'John Doe', 'type' => 'individual', 'nif' => '12345678A'],
    'company' => ['name' => 'Acme Inc', 'type' => 'company', 'nif' => 'B12345678'],
]);

dataset('invalid_emails', [
    'empty' => [''],
    'no_at' => ['invalid'],
    'no_domain' => ['test@'],
]);

// Usage in tests
it('creates client with valid data', function (array $data) {
    // Test with each dataset entry
})->with('valid_clients');

it('rejects invalid emails', function (string $email) {
    // Test with each invalid email
})->with('invalid_emails');
```

### Helper Traits

```php
// tests/Traits/CreatesClients.php
trait CreatesClients
{
    protected function createClientWithInvoices(int $invoiceCount = 3): Client
    {
        $client = Client::factory()
            ->has(Invoice::factory()->count($invoiceCount))
            ->create();

        return $client;
    }

    protected function createClientForTenant(Tenant $tenant): Client
    {
        return Client::factory()
            ->for($tenant)
            ->create();
    }
}

// Usage
uses(CreatesClients::class);

it('lists client invoices', function () {
    $client = $this->createClientWithInvoices(5);
    // ...
});
```

### Shared Actions Helper

```php
// tests/Traits/ActsAsUser.php
trait ActsAsUser
{
    protected function actingAsTenantAdmin(): self
    {
        $user = User::factory()->tenantAdmin()->create();
        $this->actingAs($user);
        return $this;
    }

    protected function actingAsTenantUser(): self
    {
        $user = User::factory()->tenantUser()->create();
        $this->actingAs($user);
        return $this;
    }
}
```

### Pest Helpers File

```php
// tests/Pest.php
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

// Global helpers
function createTenantContext(): Tenant
{
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    return $tenant;
}

function loginAsAdmin(): User
{
    $user = User::factory()->admin()->create();
    test()->actingAs($user);
    return $user;
}

// Usage in any test
it('does something', function () {
    $tenant = createTenantContext();
    $user = loginAsAdmin();
    // ...
});
```

### Reusability Checklist

- [ ] Datos repetidos → `dataset()`
- [ ] Setup repetido → `beforeEach` o Trait
- [ ] Creación de modelos → Factory methods en Traits
- [ ] Assertions comunes → Custom expectations
- [ ] Login/Auth → Helper functions en Pest.php

## Requirements

- **Coverage**: Minimum 80%
- **Type coverage**: 100%
- Commands: `composer test`, `composer test:coverage`
