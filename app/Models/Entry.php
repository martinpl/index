<?php

namespace App\Models;

use App\Enums\EntryStatus;
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
