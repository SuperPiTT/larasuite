---
paths: "**/Features/**/*.php"
description: Feature flags patterns with Laravel Pennant
---
# Feature Flags with Laravel Pennant

> **Laravel Pennant** - Docs: https://laravel.com/docs/12.x/pennant

## Feature Class Structure

```php
<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Lottery;

final class NewInvoiceEditor
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        // Gradual rollout: 20% of users
        return Lottery::odds(1, 5)->choose();
    }
}
```

## Feature with User Conditions

```php
<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;

final class AdvancedReporting
{
    public function resolve(User $user): bool
    {
        // Only for premium plan users
        return $user->tenant->plan->isPremium();
    }
}
```

## Feature with Tenant Scope

```php
<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Tenant;

final class VatAutomation
{
    public function resolve(Tenant $tenant): bool
    {
        // Enable for specific tenants or beta testers
        return $tenant->is_beta_tester
            || in_array($tenant->id, config('features.vat_automation_tenants'));
    }
}
```

## Checking Features

### In Controllers/Actions

```php
use App\Features\NewInvoiceEditor;
use Laravel\Pennant\Feature;

public function edit(Invoice $invoice)
{
    if (Feature::active(NewInvoiceEditor::class)) {
        return view('invoices.edit-v2', compact('invoice'));
    }

    return view('invoices.edit', compact('invoice'));
}
```

### In Blade Templates

```blade
@feature(App\Features\NewInvoiceEditor::class)
    <livewire:invoice-editor-v2 :invoice="$invoice" />
@else
    <livewire:invoice-editor :invoice="$invoice" />
@endfeature
```

### In Livewire Components

```php
use Laravel\Pennant\Feature;

public function render(): View
{
    return view('livewire.dashboard', [
        'showAdvancedMetrics' => Feature::active(AdvancedReporting::class),
    ]);
}
```

## Feature Activation/Deactivation

```php
// Activate for specific user
Feature::for($user)->activate(NewInvoiceEditor::class);

// Deactivate for specific user
Feature::for($user)->deactivate(NewInvoiceEditor::class);

// Activate for everyone
Feature::activateForEveryone(NewInvoiceEditor::class);

// Deactivate for everyone
Feature::deactivateForEveryone(NewInvoiceEditor::class);
```

## Feature Values (not just boolean)

```php
final class InvoiceEditorVersion
{
    public function resolve(User $user): string
    {
        return match (true) {
            $user->is_admin => 'v3-beta',
            $user->created_at->gt(now()->subMonth()) => 'v2',
            default => 'v1',
        };
    }
}

// Usage
$version = Feature::value(InvoiceEditorVersion::class);
return view("invoices.edit-{$version}");
```

## Feature Middleware

```php
// routes/web.php
Route::middleware('feature:' . NewInvoiceEditor::class)
    ->get('/invoices/editor-v2', InvoiceEditorV2Controller::class);

// With redirect on failure
Route::middleware('feature:' . NewInvoiceEditor::class . ',redirect:/invoices')
    ->get('/invoices/editor-v2', InvoiceEditorV2Controller::class);
```

## Testing Features

```php
use Laravel\Pennant\Feature;

it('shows new editor when feature is active', function () {
    Feature::activate(NewInvoiceEditor::class);

    $this->get('/invoices/1/edit')
        ->assertViewIs('invoices.edit-v2');
});

it('shows old editor when feature is inactive', function () {
    Feature::deactivate(NewInvoiceEditor::class);

    $this->get('/invoices/1/edit')
        ->assertViewIs('invoices.edit');
});

// Purge all feature state between tests
beforeEach(function () {
    Feature::purge();
});
```

## Filament Integration

```php
// In Filament Resource
public static function getPages(): array
{
    $pages = [
        'index' => Pages\ListInvoices::route('/'),
    ];

    if (Feature::active(NewInvoiceEditor::class)) {
        $pages['create'] = Pages\CreateInvoiceV2::route('/create');
        $pages['edit'] = Pages\EditInvoiceV2::route('/{record}/edit');
    } else {
        $pages['create'] = Pages\CreateInvoice::route('/create');
        $pages['edit'] = Pages\EditInvoice::route('/{record}/edit');
    }

    return $pages;
}
```

## Best Practices

1. **Class-based features**: Always use classes, not string names
2. **Descriptive names**: `NewInvoiceEditor` not `feature_123`
3. **Gradual rollout**: Use `Lottery` for percentage-based rollouts
4. **Tenant-aware**: Consider multi-tenancy in feature resolution
5. **Clean up**: Remove feature flags after full rollout
6. **Test both paths**: Always test active and inactive states
7. **Document features**: Add docblocks explaining feature purpose
