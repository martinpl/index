<?php

namespace App\EntryTypes;

use App\Models\Entry;
use App\Models\Option;
use App\Models\Site;
use DomainException;
use Illuminate\Support\Str;

class Page extends Entry
{
    public static string $type = 'page';

    /**
     * @return array{name?: string, slug?: string}
     */
    public static function config(): array
    {
        return ['slug' => ''];
    }

    public static function registered(): void
    {
        static::registerRole('home', 'Home page');
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
        $id = Option::get('page_roles', [])[$role] ?? null;

        return $id ? static::find($id) : null;
    }

    public function role(): ?string
    {
        return array_search($this->id, Option::get('page_roles', []), true) ?: null;
    }

    public function url(): string
    {
        return $this->role() === 'home' ? url(Site::current()?->path ?? '/') : parent::url();
    }
}
