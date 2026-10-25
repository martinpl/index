<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates the slug from the name when the model is saved without one, unique within its type.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->uniqueSlug(Str::slug($model->name));
            }
        });
    }

    /**
     * The given slug, suffixed with the first free number when another model of the type has it, e.g. `news-2`.
     */
    public function uniqueSlug(string $slug): string
    {
        $taken = $this->newQueryWithoutScopes()
            ->where('type', $this->type)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->where(fn (Builder $query) => $query->where('slug', $slug)->orWhereLike('slug', "$slug-%"))
            ->pluck('slug');

        $candidate = $slug;

        for ($number = 2; $taken->contains($candidate); $number++) {
            $candidate = "$slug-$number";
        }

        return $candidate;
    }
}
