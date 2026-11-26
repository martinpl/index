<?php

namespace App\Entries;

use App\Facades\Filter;
use App\Facades\Metabox;
use App\Facades\PageTemplates;
use App\Listing\Action;
use App\Listing\Tab;
use App\Models\Entry;
use App\Models\Setting;
use App\Schema\Select;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Pages can be assigned to roles, like the home page and the page shown when a page is not found. The theme and plugins
 * add templates of the pages of roles, which the site shows until users save them with their edits, as pages assigned
 * to the same role, and reset them by deleting those.
 */
class Page extends Entry
{
    public static string $type = 'page';

    /**
     * The role of the page shown, with a not found status, for the paths of the site showing no entry.
     */
    const NOT_FOUND = 'not_found';

    /**
     * The role of the template this unsaved page was made of.
     */
    public ?string $templateRole = null;

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
        static::registerRole(static::NOT_FOUND, 'Page not found');

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
            'edited' => when($page->isEdited(), __('Edited')),
        ]);

        Filter::add('entries.'.static::$type.'.actions', fn (array $actions): array => [
            ...$actions,
            Action::make('reset')
                ->label(__('Reset'))
                ->icon('lucide-rotate-ccw')
                ->confirm(__('Are you sure you want to reset the page? It is permanently deleted, showing the template it replaces again.'))
                ->visible(fn (Page $page): bool => $page->isEdited())
                ->handle(fn (Collection $pages) => $pages->each(fn (Page $page): ?bool => $page->reset()))
                ->successMessage(fn (Collection $pages): string => trans_choice(':count page reset.|:count pages reset.', $pages->count())),
        ]);

        Filter::add('entries.'.static::$type.'.tabs', fn (array $tabs): array => PageTemplates::list()->isEmpty() ? $tabs : [
            ...$tabs,
            Tab::make('templates')
                ->label(__('Templates'))
                ->query(fn (Builder|Collection $pages): Collection => static::templates()),
        ]);

        Filter::add('entry.'.static::$type.'.new', fn (Page $page, array $query): Page => is_string($query['from'] ?? null)
            ? PageTemplates::make($query['from']) ?? $page
            : $page);
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

    /**
     * The page shown for the given role: the published page assigned to it, or one made of its template.
     */
    public static function shownFor(string $role): ?Page
    {
        return static::for($role) ?? PageTemplates::make($role);
    }

    /**
     * The pages of the roles with templates, in the order the templates were added: the published page assigned to the role,
     * or one made of its template. Saved pages are keyed by ID, the others by role, after `template:` so they cannot take
     * the key of a saved one.
     *
     * @return Collection<int|string, Page>
     */
    public static function templates(): Collection
    {
        return PageTemplates::list()
            ->keys()
            ->mapWithKeys(fn (string $role): array => ($page = static::for($role)) ? [$page->getKey() => $page] : ["template:$role" => PageTemplates::make($role)]);
    }

    /**
     * The role the page is assigned to, or for a page made of a template without edits yet, the role of the template.
     */
    public function role(): ?string
    {
        if (! $this->exists) {
            return $this->templateRole;
        }

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

    /**
     * Determine if the page is assigned to a role with a template, replacing it, which resetting the page discards.
     */
    public function isEdited(): bool
    {
        return $this->exists && ! $this->trashed() && ($role = $this->role()) && PageTemplates::has($role);
    }

    /**
     * Determine if the page may be moved to the trash, which a page replacing a template may not, being reset instead.
     */
    public function trashable(): bool
    {
        return parent::trashable() && ! $this->isEdited();
    }

    /**
     * Discard the page replacing the template of its role, unassigning it, so the site shows the template again.
     */
    public function reset(): ?bool
    {
        $this->assignRole(null);

        return $this->forceDelete();
    }

    /**
     * The URL editing the page, which for a page made of a template, without edits yet, starts a new page from the template,
     * assigned to its role.
     */
    public function editUrl(): string
    {
        return $this->exists || ! $this->templateRole
            ? parent::editUrl()
            : route('dashboard.entries.create', ['type' => static::$type, 'from' => $this->templateRole]);
    }

    public function url(): string
    {
        return $this->role() === 'home' ? url('/') : parent::url();
    }
}
