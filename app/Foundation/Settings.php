<?php

namespace App\Foundation;

use App\Facades\Filter;
use App\Fields\Field;
use App\Models\Setting;
use Closure;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class Settings
{
    /**
     * @var array<string, array{key: string, title: string, page_title: string, fields: Closure(): list<Field>, can: string|null, parent: string, position: int, owner: string}>
     */
    protected array $pages = [];

    /**
     * Register a settings page whose fields are stored as settings named after each field.
     *
     * @param  array{title?: string, page_title?: string, fields: Closure(): list<Field>, can?: string|null, parent?: string, position?: int}  $config  The `title` labels the menu item, the `page_title` heads the page.
     * @param  string  $owner  Same format as the data owner column, e.g. `core` or `plugins/blog`.
     */
    public function register(string $key, array $config, string $owner): void
    {
        if ($existing = $this->get($key)) {
            throw new DomainException("Settings page [$key] is already registered by [{$existing['owner']}].");
        }

        $this->pages[$key] = [
            'key' => $key,
            'title' => $title = $config['title'] ?? Str::headline($key),
            'page_title' => $config['page_title'] ?? $title,
            'fields' => $config['fields'],
            'can' => $config['can'] ?? null,
            'parent' => $config['parent'] ?? 'settings',
            'position' => $config['position'] ?? 10,
            'owner' => $owner,
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->pages);
    }

    public function get(string $key): ?array
    {
        return $this->pages[$key] ?? null;
    }

    /**
     * @return Collection<string, array{key: string, title: string, page_title: string, fields: Closure(): list<Field>, can: string|null, parent: string, position: int, owner: string}>
     */
    public function list(): Collection
    {
        return collect($this->pages)->sortBy('position');
    }

    /**
     * The fields of the page, which plugins may change through the `settings.{key}.fields` filter.
     *
     * @return list<Field>
     */
    public function fields(string $key): array
    {
        return Filter::apply("settings.$key.fields", ($this->pages[$key]['fields'])(), $key);
    }

    public function allows(string $key): bool
    {
        $can = $this->pages[$key]['can'] ?? null;

        return is_null($can) || Gate::allows($can);
    }

    /**
     * The stored values of the page, keyed by field name, falling back to each field's default.
     *
     * @return array<string, mixed>
     */
    public function values(string $key): array
    {
        return collect($this->fields($key))
            ->mapWithKeys(fn (Field $field): array => [$field->name => $field->cast(Setting::get($field->name, $field->getDefault()))])
            ->all();
    }

    /**
     * Store the given values of the page, forgetting empty ones and ignoring any value the page has no field for.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(string $key, array $values): void
    {
        foreach ($this->fields($key) as $field) {
            $value = $field->cast($values[$field->name] ?? null);

            Setting::set($field->name, $value, $this->pages[$key]['owner']);
        }
    }
}
