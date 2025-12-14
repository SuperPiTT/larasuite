---
paths: "**/Filament/**/*.php"
description: FilamentPHP 4 patterns for admin panel resources
---
# FilamentPHP 4 Patterns

> **FilamentPHP 4.x** - Docs: https://filamentphp.com/docs/4.x
>
> Useful sections:
> - Forms: https://filamentphp.com/docs/4.x/forms
> - Tables: https://filamentphp.com/docs/4.x/tables
> - Panels: https://filamentphp.com/docs/4.x/panels
> - Notifications: https://filamentphp.com/docs/4.x/notifications

## Resource Structure

```php
final class ClientResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'CRM';

    // Eager load relationships
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['invoices', 'contacts']);
    }
}
```

## Forms

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Informacion Basica')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
            ])->columns(2),
    ]);
}
```

## Tables

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'warning' => 'pending',
                    'success' => 'paid',
                ]),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('status'),
        ]);
}
```

## Multi-tenancy

```php
// Tenant-scoped queries (already handled by panel config)
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->whereBelongsTo(Filament::getTenant());
}
```

## Actions

```php
Tables\Actions\Action::make('send_reminder')
    ->label('Enviar Recordatorio')
    ->icon('heroicon-o-paper-airplane')
    ->requiresConfirmation()
    ->action(fn (Invoice $record) => $record->sendReminder())
    ->visible(fn (Invoice $record) => $record->isPending());
```

## Performance

- Use `withCount()` instead of loading full relations for counts
- Defer heavy widgets with `protected static bool $isLazy = true`
- Paginate tables (default 15-25 items)
