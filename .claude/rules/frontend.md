---
paths: "**/*.blade.php, **/Livewire/**/*.php, resources/js/**/*.js"
description: Livewire 3, Alpine.js and Blade patterns
---
# Frontend Patterns

> **Livewire 3.x** - Docs: https://livewire.laravel.com/docs
> **Alpine.js 3.x** - Docs: https://alpinejs.dev/docs
> **Tailwind CSS 4.x** - Docs: https://tailwindcss.com/docs

## Livewire 3

### Component Structure

```php
#[Layout('layouts.app')]
#[Title('Clients')]
final class ClientList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    public function render(): View
    {
        return view('livewire.client-list', [
            'clients' => Client::search($this->search)->paginate(15),
        ]);
    }
}
```

### Wire Model (Livewire 3 defaults)

```blade
{{-- Deferred by default (no server call on each keystroke) --}}
<input type="text" wire:model="name">

{{-- Live updates (explicit) --}}
<input type="text" wire:model.live="search">

{{-- On blur --}}
<input type="text" wire:model.blur="email">
```

### Events

```php
// Dispatch
$this->dispatch('client-updated', clientId: $client->id);

// Listen
#[On('client-updated')]
public function refreshList(int $clientId): void {}
```

## Alpine.js

### Use for UI-only state (no server roundtrip)

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Content</div>
</div>
```

### Sync with Livewire

```blade
<div x-data="{ search: $wire.entangle('search') }">
    <input x-model="search">
    <button @click="$wire.save()">Save</button>
</div>
```

### Preserve during Livewire updates

```blade
<div wire:ignore>
    <div x-data="richTextEditor()">...</div>
</div>
```

## Blade Components

```blade
{{-- resources/views/components/button.blade.php --}}
@props(['variant' => 'primary', 'size' => 'md'])

<button {{ $attributes->merge(['class' => "btn btn-{$variant} btn-{$size}"]) }}>
    {{ $slot }}
</button>

{{-- Usage --}}
<x-button variant="danger" wire:click="delete">Delete</x-button>
```

## Security

```blade
{{-- ALWAYS escaped (default) --}}
{{ $user->name }}

{{-- DANGER - only trusted, sanitized HTML --}}
{!! $trustedHtml !!}

{{-- CSRF on forms --}}
<form method="POST">@csrf ...</form>
```

## Performance

- Use `wire:key` in loops: `<div wire:key="item-{{ $item->id }}">`
- Lazy load heavy components: `<livewire:stats lazy />`
- Use Alpine for UI state to avoid server roundtrips
