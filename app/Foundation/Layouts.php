<?php

namespace App\Foundation;

use App\Entries\Layout;
use Closure;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The layouts added by the theme and plugins, like a default one with a navbar and a footer and a blank one, keyed by
 * their slug. The site shows them as they were added until users save them with their edits, as layouts of the same slug.
 */
class Layouts
{
    /**
     * @var array<string, array{key: string, name: string, content: Closure|list<array<string, mixed>>, owner: string}>
     */
    protected array $layouts = [];

    /**
     * Register a layout made of the given blocks, placing the content of its entries in its content block. The `default`
     * layout shows the entries that choose no other.
     *
     * @param  array{name?: string, content?: list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>|Closure(): list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>}  $config
     * @param  string  $owner  Same format as the data owner column, e.g. `core` or `themes/howdy`.
     */
    public function register(string $key, array $config, string $owner): void
    {
        if ($existing = $this->get($key)) {
            throw new DomainException("Layout [$key] is already registered by [{$existing['owner']}].");
        }

        $this->layouts[$key] = [
            'key' => $key,
            'name' => $config['name'] ?? Str::headline($key),
            'content' => $config['content'] ?? [],
            'owner' => $owner,
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->layouts);
    }

    /**
     * @return array{key: string, name: string, content: Closure|list<array<string, mixed>>, owner: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->layouts[$key] ?? null;
    }

    /**
     * The layouts in the order they were registered.
     *
     * @return Collection<string, array{key: string, name: string, content: Closure|list<array<string, mixed>>, owner: string}>
     */
    public function list(): Collection
    {
        return collect($this->layouts);
    }

    /**
     * A new, unsaved layout entry made of the registered layout, showing it until users save it with their edits,
     * or null when it is not registered.
     */
    public function make(string $key): ?Layout
    {
        if (! $layout = $this->get($key)) {
            return null;
        }

        return new Layout([
            'slug' => $key,
            'name' => __($layout['name']),
            'content' => value($layout['content']),
            'owner' => $layout['owner'],
        ]);
    }
}
