---
paths: "**/migrations/**/*.php"
description: Database migration patterns
---
# Migration Patterns

## Structure

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Specify connection for multi-tenancy
    protected $connection = 'tenant'; // or 'landlord'

    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('number', 20)->unique();
            $table->string('status', 20)->default('draft');
            $table->decimal('total', 12, 2);
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['client_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    // ALWAYS implement down()
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
```

## Multi-tenancy Locations

```
database/
├── migrations/           # Landlord (central) migrations
└── tenant/
    └── migrations/       # Tenant-specific migrations
```

## Indexing Guidelines

```php
// Single column for frequent filters
$table->index('status');

// Composite for combined queries (order matters!)
$table->index(['client_id', 'status']);

// Unique constraints
$table->unique(['tenant_id', 'invoice_number']);
```

## Foreign Keys

```php
// With cascade delete
$table->foreignId('client_id')->constrained()->cascadeOnDelete();

// With set null
$table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

// Custom reference
$table->foreignId('category_id')->constrained('product_categories');
```

## Column Types (PostgreSQL)

```php
$table->decimal('amount', 12, 2);    // Money
$table->jsonb('metadata');            // JSON with indexing
$table->timestampTz('scheduled_at');  // Timezone-aware
$table->uuid('public_id')->unique();  // Public identifiers
```
