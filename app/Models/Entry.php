<?php

namespace App\Models;

use App\Enums\EntryStatus;
use App\Facades\EntryType;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasMeta;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @method static Builder<static> withAnyStatus()
 */
#[Fillable(['type', 'status', 'slug', 'name', 'content', 'user_id', 'parent_id', 'order'])]
class Entry extends Model
{
    use BelongsToSite;

    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    use HasMeta;

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

    protected static function booted(): void
    {
        static::addGlobalScope('published', function (Builder $query): void {
            $query->where($query->qualifyColumn('status'), EntryStatus::Publish);
        });

        if (static::class !== self::class) {
            static::addGlobalScope('type', function (Builder $query): void {
                $query->where($query->qualifyColumn('type'), static::$type);
            });

            static::creating(function (Entry $entry): void {
                $entry->type ??= static::$type;
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
