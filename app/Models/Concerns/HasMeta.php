<?php

namespace App\Models\Concerns;

use App\Models\Meta;
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
        $this->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function forgetMeta(string $key): int
    {
        return $this->meta()->where('key', $key)->delete();
    }
}
