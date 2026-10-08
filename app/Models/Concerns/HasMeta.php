<?php

namespace App\Models\Concerns;

use App\Models\Meta;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMeta
{
    public static function bootHasMeta(): void
    {
        static::deleted(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->meta()->delete();
        });
    }

    /**
     * @return MorphMany<Meta, $this>
     */
    public function meta(): MorphMany
    {
        return $this->morphMany(Meta::class, 'metable');
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta()->where('key', $key)->value('value') ?? $default;
    }

    public function hasMeta(string $key): bool
    {
        return $this->meta()->where('key', $key)->exists();
    }

    public function setMeta(string $key, mixed $value): void
    {
        if ($value === null || $value === []) {
            $this->forgetMeta($key);

            return;
        }

        $this->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function forgetMeta(string $key): int
    {
        return $this->meta()->where('key', $key)->delete();
    }

    /**
     * Scope to models having the given meta, optionally with a value matching the given comparison.
     */
    #[Scope]
    protected function whereMeta(Builder $query, string $key, mixed $operator = null, mixed $value = null): void
    {
        $hasComparison = func_num_args() > 2;

        [$value, $operator] = $query->getQuery()->prepareValueAndOperator($value, $operator, func_num_args() === 3);

        $query->whereHas('meta', fn (Builder $query) => $query
            ->where('key', $key)
            ->when($hasComparison, fn (Builder $query) => $query->whereValue($operator, $value)));
    }

    /**
     * Scope to models having the given meta with a value that is or contains the given value.
     */
    #[Scope]
    protected function whereMetaContains(Builder $query, string $key, mixed $value): void
    {
        $query->whereHas('meta', fn (Builder $query) => $query
            ->where('key', $key)
            // Qualified, as SQLite would resolve a bare "value" to the json_each() column.
            ->whereJsonContains($query->qualifyColumn('value'), $value));
    }
}
