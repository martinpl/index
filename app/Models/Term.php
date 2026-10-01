<?php

namespace App\Models;

use App\Facades\TermType;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasMeta;
use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['type', 'slug', 'name', 'description', 'parent_id'])]
class Term extends Model
{
    use BelongsToSite;

    /** @use HasFactory<TermFactory> */
    use HasFactory;

    use HasMeta;

    /**
     * The term type key, required in term type classes.
     */
    public static string $type;

    public $timestamps = false;

    protected $table = 'terms';

    protected static function booted(): void
    {
        if (static::class !== self::class) {
            static::addGlobalScope('type', function (Builder $query): void {
                $query->where($query->qualifyColumn('type'), static::$type);
            });

            static::creating(function (Term $term): void {
                $term->type ??= static::$type;
            });
        }
    }

    /**
     * The term type config when this class is registered as a term type.
     *
     * @return array{name?: string, entry_types?: list<string>}
     */
    public static function config(): array
    {
        return [];
    }

    /**
     * Hydrate each row as the class registered for its term type.
     */
    public function newFromBuilder($attributes = [], $connection = null): static
    {
        $model = TermType::get(((array) $attributes)['type'] ?? '')['model'] ?? null;

        if ($model && is_subclass_of($model, static::class)) {
            return (new $model)->newFromBuilder($attributes, $connection);
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    public function getMorphClass(): string
    {
        return 'term';
    }

    public function getForeignKey(): string
    {
        return 'term_id';
    }

    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, $this->siteTable('entry_term'));
    }
}
