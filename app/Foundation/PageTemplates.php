<?php

namespace App\Foundation;

use App\Entries\Page;
use App\Enums\EntryStatus;
use Closure;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The templates of the pages of roles, like the page shown when a page is not found, added by the theme and plugins and
 * keyed by role. The site shows them as they were added until users save them with their edits, as pages assigned
 * to the same role.
 */
class PageTemplates
{
    /**
     * @var array<string, array{key: string, name: string, slug: string, content: Closure|list<array<string, mixed>>, owner: string}>
     */
    protected array $templates = [];

    /**
     * Register the template of the page shown for the given page role until a published page is assigned to it,
     * made of the given blocks.
     *
     * @param  array{name?: string, slug?: string, content?: list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>|Closure(): list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>}  $config
     * @param  string  $owner  Same format as the data owner column, e.g. `core` or `themes/howdy`.
     */
    public function register(string $role, array $config, string $owner): void
    {
        if ($existing = $this->get($role)) {
            throw new DomainException("Page template [$role] is already registered by [{$existing['owner']}].");
        }

        $name = $config['name'] ?? Str::headline($role);

        $this->templates[$role] = [
            'key' => $role,
            'name' => $name,
            'slug' => $config['slug'] ?? Str::slug($name),
            'content' => $config['content'] ?? [],
            'owner' => $owner,
        ];
    }

    public function has(string $role): bool
    {
        return array_key_exists($role, $this->templates);
    }

    /**
     * @return array{key: string, name: string, slug: string, content: Closure|list<array<string, mixed>>, owner: string}|null
     */
    public function get(string $role): ?array
    {
        return $this->templates[$role] ?? null;
    }

    /**
     * The templates in the order they were registered.
     *
     * @return Collection<string, array{key: string, name: string, slug: string, content: Closure|list<array<string, mixed>>, owner: string}>
     */
    public function list(): Collection
    {
        return collect($this->templates);
    }

    /**
     * A new, unsaved, published page made of the template of the role, which it is shown for until users save it
     * with their edits, or null when the role has no template.
     */
    public function make(string $role): ?Page
    {
        if (! $template = $this->get($role)) {
            return null;
        }

        return tap(new Page([
            'type' => Page::$type,
            'slug' => $template['slug'],
            'name' => __($template['name']),
            'content' => value($template['content']),
            'status' => EntryStatus::Publish->value,
            'owner' => $template['owner'],
        ]), fn (Page $page): string => $page->templateRole = $role);
    }
}
