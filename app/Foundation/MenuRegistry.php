<?php

namespace App\Foundation;

use App\Facades\Action;
use Closure;
use DomainException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

abstract class MenuRegistry
{
    /**
     * @var array<string, array{key: string, title: string|Htmlable|Closure, url: string|Closure|null, icon: string|null, parent: string|null, position: int, can: string|Closure|null, align: string}>
     */
    protected array $items = [];

    /**
     * The action fired with the menu before it is first built, after every package has booted.
     */
    protected string $hook;

    /**
     * Add an item; `icon` is a Blade component name, `can` an ability or a closure receiving the user,
     * and `align` places a top-level item at the `start` or `end`. An item whose title is Htmlable, such as
     * a view, renders its own markup and needs no `url`.
     *
     * @param  array{title: string|Htmlable|Closure, url?: string|Closure|null, icon?: string|null, parent?: string|null, position?: int, can?: string|Closure|null, align?: 'start'|'end'}  $item
     */
    public function add(string $key, array $item): void
    {
        if ($this->has($key)) {
            throw new DomainException("Menu item [$key] is already registered.");
        }

        $this->items[$key] = [
            'key' => $key,
            'title' => $item['title'],
            'url' => $item['url'] ?? null,
            'icon' => $item['icon'] ?? null,
            'parent' => $item['parent'] ?? null,
            'position' => $item['position'] ?? 10,
            'can' => $item['can'] ?? null,
            'align' => $item['align'] ?? 'start',
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function get(string $key): ?array
    {
        return $this->items[$key] ?? null;
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * The items visible to the current user, resolved, sorted by position and nested under their parents.
     *
     * @return Collection<int, array{key: string, title: string|Htmlable, url: string|null, icon: string|null, align: string, active: bool, children: Collection}>
     */
    public function tree(?string $parent = null): Collection
    {
        if (! Action::fired($this->hook)) {
            Action::run($this->hook, $this);
        }

        return collect($this->items)
            ->where('parent', $parent)
            ->filter($this->visible(...))
            ->sortBy('position')
            ->map(fn (array $item): array => [
                'key' => $item['key'],
                'title' => value($item['title']),
                'url' => $url = value($item['url']),
                'icon' => $item['icon'],
                'align' => $item['align'],
                'active' => $url === request()->url(),
                'children' => $this->tree($item['key']),
            ])
            ->values();
    }

    /**
     * @param  array{can: string|Closure|null}  $item
     */
    protected function visible(array $item): bool
    {
        return match (true) {
            is_null($item['can']) => true,
            is_string($item['can']) => Gate::allows($item['can']),
            default => (bool) $item['can'](Auth::user()),
        };
    }
}
