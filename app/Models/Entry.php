<?php

namespace App\Models;

use App\Blocks\BlockCollection;
use App\Casts\AsBlocks;
use App\Entries\Layout;
use App\Enums\EntryStatus;
use App\Facades\EntryType;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasMeta;
use App\Models\Concerns\HasOwner;
use App\Models\Concerns\HasSlug;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

/**
 * @property BlockCollection $content
 *
 * @method static Builder<static> withAnyStatus()
 */
#[Fillable(['type', 'status', 'slug', 'name', 'content', 'user_id', 'parent_id', 'order', 'owner'])]
class Entry extends Model
{
    use BelongsToSite;

    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    use HasMeta;
    use HasOwner;
    use HasSlug;

    const CREATED_AT = 'date';

    const UPDATED_AT = 'modified';

    /**
     * The entry type key, required in entry type classes.
     */
    public static string $type;

    protected $table = 'entries';

    protected $attributes = [
        'status' => EntryStatus::Draft->value,
        'order' => 0,
    ];

    protected bool $forceDeleting = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => AsBlocks::class,
        ];
    }

    protected static function booting(): void
    {
        // Registered before the traits boot, so `HasSlug` makes the slug unique within the type.
        if (static::class !== self::class) {
            static::saving(function (Entry $entry): void {
                $entry->type ??= static::$type;
            });
        }
    }

    protected static function booted(): void
    {
        static::addGlobalScope('published', function (Builder $query): void {
            $query->where($query->qualifyColumn('status'), EntryStatus::Publish);
        });

        if (static::class !== self::class) {
            static::addGlobalScope('type', function (Builder $query): void {
                $query->where($query->qualifyColumn('type'), static::$type);
            });
        }
    }

    /**
     * The entry type config when this class is registered as an entry type.
     *
     * @return array{name?: string, slug?: string}
     */
    public static function config(): array
    {
        return [];
    }

    /**
     * Called after this class is registered as an entry type.
     */
    public static function registered(): void
    {
        //
    }

    /**
     * Hydrate each row as the class registered for its entry type.
     */
    public function newFromBuilder($attributes = [], $connection = null): static
    {
        $model = EntryType::get(((array) $attributes)['type'] ?? '')['model'] ?? null;

        if ($model && is_subclass_of($model, static::class)) {
            return (new $model)->newFromBuilder($attributes, $connection);
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    public function getMorphClass(): string
    {
        return 'entry';
    }

    public function getForeignKey(): string
    {
        return 'entry_id';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Entry, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * @return BelongsToMany<Term, $this>
     */
    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(Term::class, $this->siteTable('entry_term'));
    }

    /**
     * @return HasMany<EntryMeta, $this>
     */
    public function meta(): HasMany
    {
        return $this->hasMany(EntryMeta::class);
    }

    /**
     * The entry the site is showing, or null when it shows none, like on the dashboard.
     */
    public static function current(): ?Entry
    {
        return app()->bound(self::class) ? app(self::class) : null;
    }

    public function url(): string
    {
        return url($this->path());
    }

    public function path(): string
    {
        $slugs = [];
        for ($entry = $this; $entry; $entry = $entry->parent) {
            array_unshift($slugs, $entry->slug);
        }

        return ltrim((EntryType::get($this->type)['slug'] ?? '').'/'.implode('/', $slugs), '/');
    }

    public function trashed(): bool
    {
        return $this->status === EntryStatus::Trash->value;
    }

    /**
     * The URL editing the entry in the dashboard.
     */
    public function editUrl(): string
    {
        return route('dashboard.entries.edit', [$this->type, $this]);
    }

    /**
     * Determine if the entry may be moved to the trash, which only saved entries that are not trashed yet may.
     */
    public function trashable(): bool
    {
        return $this->exists && ! $this->trashed();
    }

    /**
     * Determine if the entries of the type are internal, used by the site without being its content, like layouts.
     */
    public function isInternal(): bool
    {
        return $this->newInstance()->status === EntryStatus::Internal->value;
    }

    /**
     * The layout the entry is shown in: the one chosen for it, or the default one, while internal entries have none.
     * Remembered for the entry until another one is chosen.
     */
    public function layout(): ?Layout
    {
        if ($this->isInternal()) {
            return null;
        }

        $slug = $this->getMeta('layout');

        return once(fn (): ?Layout => Layout::namedOrDefault($slug));
    }

    /**
     * The HTML showing the entry on the site: its content in its layout, or alone when it has none.
     */
    public function render(): HtmlString
    {
        return $this->layout()?->wrap($this->content) ?? new HtmlString($this->content->toHtml());
    }

    /**
     * Restore the trashed entry to the status the entries of its type start with, like draft.
     */
    public function restore(): bool
    {
        return $this->update(['status' => $this->newInstance()->status]);
    }

    public function forceDelete(): ?bool
    {
        $this->forceDeleting = true;

        return tap($this->delete(), fn () => $this->forceDeleting = false);
    }

    public function isForceDeleting(): bool
    {
        return $this->forceDeleting;
    }

    protected function performDeleteOnModel(): void
    {
        if ($this->forceDeleting) {
            parent::performDeleteOnModel();

            return;
        }

        $this->update(['status' => EntryStatus::Trash->value]);
    }

    #[Scope]
    protected function withAnyStatus(Builder $query): void
    {
        $query->withoutGlobalScope('published');
    }
}
