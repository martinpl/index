<?php

namespace App\Entries;

use App\Blocks\BlockCollection;
use App\Blocks\Content;
use App\Enums\EntryStatus;
use App\Facades\EntryType;
use App\Facades\Filter;
use App\Facades\Layouts;
use App\Facades\Metabox;
use App\Listing\Action;
use App\Models\Entry;
use App\Schema\Select;
use Database\Factories\LayoutFactory;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Blocks shown around the content of the entries, like a navbar above it and a footer below it, placing the content
 * in their content block, or after them without one. Entries are shown in the layout chosen for them, or the default one,
 * referring to it by its slug.
 *
 * The theme and plugins add layouts, which the site shows as they were added until users save them with their edits,
 * as layouts of the same slug, and reset them by deleting those. Users add layouts of their own too, which they trash.
 * Layouts have the `internal` status, so they are not served as pages.
 *
 * @property BlockCollection $content
 */
#[UseFactory(LayoutFactory::class)]
class Layout extends Entry
{
    public static string $type = 'layout';

    /**
     * The slug of the layout showing the entries that choose no other.
     */
    const DEFAULT = 'default';

    protected $attributes = [
        'status' => EntryStatus::Internal->value,
        'order' => 0,
    ];

    /**
     * Listed under the appearance of the site, by those who may manage its themes.
     *
     * @return array{name?: string, slug?: string, icon?: string, can?: string, listing?: string, editor?: string, menu_parent?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-layout-template', 'can' => 'themes.manage', 'menu_parent' => 'appearance'];
    }

    /**
     * Let the editors of the entries served on the site choose their layout, list the layouts added
     * by the theme and plugins along with the saved ones, marking the default and the edited ones, letting users reset
     * these, and start a new layout from the one added by the theme or a plugin that the editor is opened `from`,
     * so saving it keeps the edits as a layout of the same slug.
     */
    public static function registered(): void
    {
        $type = static::$type;

        Metabox::register('layout', [
            'schema' => fn (): array => [
                Select::make('layout')
                    ->label(__('Layout'))
                    ->description(__('The blocks shown around the content, like a navbar and a footer.'))
                    ->options(fn (): array => static::available()->map(fn (Layout $layout): string => $layout->name)->all())
                    ->visible(fn (): bool => static::available()->isNotEmpty())
                    ->view('block-editor.layout-picker'),
            ],
            'values' => fn (?Entry $entry): array => ['layout' => static::namedOrDefault($entry?->getMeta('layout'))?->slug],
            'save' => function (Entry $entry, array $values): void {
                filled($values['layout']) && $values['layout'] !== static::DEFAULT
                    ? $entry->setMeta('layout', $values['layout'], 'core')
                    : $entry->forgetMeta('layout');
            },
            'with' => ['title' => 'Layout', 'seamless' => true],
            'location' => fn (): array => EntryType::list()
                ->reject(fn (array $entryType): bool => ($entry = new $entryType['model']) instanceof Entry && $entry->isInternal())
                ->map(fn (array $entryType): string => "entry.{$entryType['key']}")
                ->values()
                ->all(),
            'position' => 5,
        ], 'core');

        Filter::add("entries.$type.records", fn (): Collection => static::listed());

        Filter::add("entries.$type.states", fn (array $states, Layout $layout): array => [
            ...$states,
            'default_layout' => when($layout->slug === static::DEFAULT && ! $layout->trashed(), __('Default')),
            'edited' => when($layout->isEdited(), __('Edited')),
        ]);

        Filter::add("entries.$type.actions", fn (array $actions): array => [
            ...$actions,
            Action::make('reset')
                ->label(__('Reset'))
                ->icon('lucide-rotate-ccw')
                ->confirm(__('Are you sure you want to reset the layout? Your edits are discarded, showing the layout as it was added.'))
                ->visible(fn (Layout $layout): bool => $layout->isEdited())
                ->handle(fn (Collection $layouts) => $layouts->each(fn (Layout $layout): ?bool => $layout->reset()))
                ->successMessage(fn (Collection $layouts): string => trans_choice(':count layout reset.|:count layouts reset.', $layouts->count())),
        ]);

        Filter::add("entry.$type.new", fn (Layout $layout, array $query): Layout => is_string($query['from'] ?? null)
            ? Layouts::make($query['from']) ?? $layout
            : $layout);
    }

    protected static function booted(): void
    {
        parent::booted();

        // Replaces the scope to published entries, as layouts are internal entries.
        static::addGlobalScope('published', function (Builder $query): void {
            $query->where($query->qualifyColumn('status'), EntryStatus::Internal);
        });
    }

    /**
     * The layouts of the site keyed by slug: the ones added by the theme and plugins, in the order they were added and
     * as they were added unless saved with edits, followed by the ones added by users, by name.
     *
     * @return Collection<string, Layout>
     */
    public static function available(): Collection
    {
        $saved = static::query()->orderBy('name')->get()->keyBy('slug');

        return Layouts::list()
            ->map(fn (array $layout, string $key): Layout => $saved[$key] ?? Layouts::make($key))
            ->union($saved);
    }

    /**
     * The layouts the dashboard lists: the ones added by the theme and plugins, in the order they were added and as they
     * were added unless saved with edits, followed by the other saved ones by name, trashed ones too. Saved layouts are
     * keyed by ID, the others by slug, after `added:` so they cannot take the key of a saved one.
     *
     * @return Collection<int|string, Layout>
     */
    public static function listed(): Collection
    {
        $saved = static::query()->withAnyStatus()->with('user')->orderBy('name')->get();
        $edited = $saved->reject(fn (Layout $layout): bool => $layout->trashed())->keyBy('slug');

        return Layouts::list()
            ->keys()
            ->mapWithKeys(fn (string $key): array => isset($edited[$key]) ? [$edited[$key]->getKey() => $edited[$key]] : ["added:$key" => Layouts::make($key)])
            ->union($saved->toBase()->keyBy(fn (Layout $layout): int => $layout->getKey()));
    }

    /**
     * The layout with the given slug: the one saved with it, or the one added by the theme or a plugin, or null for none.
     */
    public static function named(?string $slug): ?Layout
    {
        return filled($slug) ? static::query()->firstWhere('slug', $slug) ?? Layouts::make($slug) : null;
    }

    /**
     * The layout showing the entries that choose no other.
     */
    public static function default(): ?Layout
    {
        return static::named(static::DEFAULT);
    }

    /**
     * The layout with the given slug, or the default one when there is none.
     */
    public static function namedOrDefault(?string $slug): ?Layout
    {
        return static::named($slug) ?? static::default();
    }

    /**
     * The layout chosen in the layout metabox among the given values of the metaboxes of an editor, or null when the editor
     * shows no layout metabox, like the editors of internal entries.
     *
     * @param  array<string, array<string, mixed>>  $metaboxes
     */
    public static function chosenIn(array $metaboxes): ?Layout
    {
        return array_key_exists('layout', $metaboxes) ? static::namedOrDefault($metaboxes['layout']['layout'] ?? null) : null;
    }

    /**
     * Determine if the layout was added by the theme or a plugin and saved with edits, which resetting it discards.
     */
    public function isEdited(): bool
    {
        return $this->exists && ! $this->trashed() && Layouts::has($this->slug);
    }

    /**
     * Determine if the layout may be moved to the trash, which a layout added by the theme or a plugin may not,
     * being reset instead.
     */
    public function trashable(): bool
    {
        return parent::trashable() && ! $this->isEdited();
    }

    /**
     * The URL editing the layout, which for a layout added by the theme or a plugin, without edits yet,
     * starts a new layout from it.
     */
    public function editUrl(): string
    {
        return $this->exists
            ? route('dashboard.entries.edit', [static::$type, $this])
            : route('dashboard.entries.create', ['type' => static::$type, 'from' => $this->slug]);
    }

    /**
     * The blocks of the layout before its first content block, that block and the blocks after it, or all of its blocks
     * without a content block, as the content then follows them.
     *
     * @return array{0: BlockCollection, 1: Content|null, 2: BlockCollection}
     */
    public function aroundContent(): array
    {
        foreach ($this->content->values() as $index => $block) {
            if ($block instanceof Content) {
                return [$this->content->take($index), $block, $this->content->slice($index + 1)];
            }
        }

        return [$this->content, null, new BlockCollection];
    }

    /**
     * The HTML of the blocks of the layout, showing the given content in their first content block, or after them without one.
     */
    public function wrap(Htmlable|string $content): HtmlString
    {
        [$html, $shown] = Content::showing($content, fn (): string => $this->content->toHtml());

        if (! $shown) {
            $html = implode("\n", array_filter([$html, $content instanceof Htmlable ? $content->toHtml() : $content]));
        }

        return new HtmlString($html);
    }

    /**
     * Discard the edits of the layout added by the theme or a plugin, so the site shows it as it was added again.
     */
    public function reset(): ?bool
    {
        return $this->forceDelete();
    }
}
