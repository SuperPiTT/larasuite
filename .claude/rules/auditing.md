---
paths: "**/Models/**/*.php, **/Traits/**/*.php"
description: Data auditing patterns for tracking user actions and changes
---
# Data Auditing Patterns

## UserActions Trait

Track who created, updated, and deleted records:

```php
<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait UserActions
{
    public static function bootUserActions(): void
    {
        static::creating(function ($model): void {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model): void {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        if (method_exists(static::class, 'deleting')) {
            static::deleting(function ($model): void {
                if (Auth::check() && $model->usesSoftDeletes()) {
                    $model->deleted_by = Auth::id();
                    $model->saveQuietly();
                }
            });
        }
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    private function usesSoftDeletes(): bool
    {
        return in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($this));
    }
}
```

## Migration for UserActions

```php
// Add to existing migration or create a new one
Schema::table('invoices', function (Blueprint $table) {
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
});
```

## LogChanges Trait

Track all field changes with history:

```php
<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\ChangeLog;
use Illuminate\Support\Facades\Auth;

trait LogChanges
{
    /** @var array<string> Fields to exclude from logging */
    protected array $excludeFromLog = ['updated_at', 'updated_by'];

    public static function bootLogChanges(): void
    {
        static::created(function ($model): void {
            $model->logChange('created', [], $model->getAttributes());
        });

        static::updated(function ($model): void {
            $changes = $model->getChanges();
            $original = array_intersect_key($model->getOriginal(), $changes);

            // Filter excluded fields
            $excluded = $model->excludeFromLog ?? [];
            $changes = array_diff_key($changes, array_flip($excluded));
            $original = array_diff_key($original, array_flip($excluded));

            if (!empty($changes)) {
                $model->logChange('updated', $original, $changes);
            }
        });

        static::deleted(function ($model): void {
            $model->logChange('deleted', $model->getOriginal(), []);
        });
    }

    protected function logChange(string $action, array $oldValues, array $newValues): void
    {
        ChangeLog::create([
            'model_type' => static::class,
            'model_id' => $this->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function changeLogs()
    {
        return $this->morphMany(ChangeLog::class, 'model');
    }
}
```

## ChangeLog Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ChangeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## ChangeLog Migration

```php
Schema::create('change_logs', function (Blueprint $table) {
    $table->id();
    $table->string('model_type');
    $table->unsignedBigInteger('model_id');
    $table->string('action', 20); // created, updated, deleted
    $table->jsonb('old_values')->nullable();
    $table->jsonb('new_values')->nullable();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['model_type', 'model_id']);
    $table->index('user_id');
    $table->index('created_at');
});
```

## Usage in Models

```php
final class Invoice extends Model
{
    use UserActions;
    use LogChanges;

    // Exclude sensitive or noisy fields
    protected array $excludeFromLog = [
        'updated_at',
        'updated_by',
        'remember_token',
    ];
}
```

## Filament Integration

Display audit info in resources:

```php
// In form schema
Forms\Components\Placeholder::make('audit')
    ->content(fn (Invoice $record): string =>
        "Created by {$record->creator?->name} on {$record->created_at->format('d/m/Y H:i')}"
    )
    ->visibleOn('edit');

// Audit history relation manager
Tables\Columns\TextColumn::make('action')
    ->badge()
    ->color(fn (string $state) => match ($state) {
        'created' => 'success',
        'updated' => 'warning',
        'deleted' => 'danger',
    });
```

## Multi-tenancy Consideration

For tenant databases, use the ChangeLog model in the tenant connection:

```php
final class ChangeLog extends Model
{
    protected $connection = 'tenant';
}
```
