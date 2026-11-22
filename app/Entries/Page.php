<?php

namespace App\Entries;

use App\Facades\Filter;
use App\Facades\Metabox;
use App\Models\Entry;
use App\Models\Setting;
use App\Schema\Select;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Page extends Entry
{
    public static string $type = 'page';

    /**
     * @return array{name?: string, slug?: string, icon?: string, can?: string}
     */
    public static function config(): array
    {
        return ['slug' => '', 'icon' => 'lucide-file'];
    }

    public static function registered(): void
    {
        static::registerRole('home', 'Home page');

        Metabox::register('page-role', [
            'schema' => fn (): array => [
                Select::make('role')
                    ->label(__('Role'))
                    ->options(fn (): array => array_map(__(...), static::roles())),
            ],
            'values' => fn (?Page $page): array => ['role' => in_array($role = $page?->role(), array_keys(static::roles()), true) ? $role : null],
            'save' => fn (Entry $entry, array $values) => static::query()->withAnyStatus()->findOrFail($entry->id)->assignRole($values['role']),
            'with' => ['title' => 'Page role', 'seamless' => true],
            'location' => 'entry.page',
            'position' => 0,
        ], 'core');

        Filter::add('entries.'.static::$type.'.states', fn (array $states, Page $page): array => [
            ...$states,
            'page_role' => ($name = static::roles()[$page->role()] ?? null) ? __($name) : null,
        ]);
    }

    /**
     * Roles, like the home page, that a page can be assigned to.
     *
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return app()->bound('page.roles') ? app('page.roles') : [];
    }

    public static function registerRole(string $key, ?string $name = null): void
    {
        $roles = static::roles();
        if (array_key_exists($key, $roles)) {
            throw new DomainException("Page role [$key] is already registered.");
        }

        app()->instance('page.roles', [...$roles, $key => $name ?? Str::headline($key)]);
    }

    /**
     * The published page assigned to the given role.
     */
    public static function for(string $role): ?static
    {
        $id = Setting::get('page_roles', [])[$role] ?? null;

        return $id ? static::find($id) : null;
    }

    public function role(): ?string
    {
        return array_search($this->id, Setting::get('page_roles', []), true) ?: null;
    }

    /**
     * Assign the page to the given role, taking the role from the page holding it, or unassign the page from its role when null.
     * Roles that are no longer registered keep their pages.
     */
    public function assignRole(?string $role): void
    {
        $roles = collect(Setting::get('page_roles', []))
            ->reject(fn (int $id, string $key): bool => $id === $this->id && array_key_exists($key, static::roles()))
            ->when($role, fn (Collection $roles): Collection => $roles->put($role, $this->id));

        Setting::set('page_roles', $roles->all(), 'core');
    }

    public function url(): string
    {
        return $this->role() === 'home' ? url('/') : parent::url();
    }
}
