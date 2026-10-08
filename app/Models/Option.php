<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasOwner;
use Database\Factories\OptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'owner'])]
class Option extends Model
{
    use BelongsToSite;

    /** @use HasFactory<OptionFactory> */
    use HasFactory;

    use HasOwner;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function exists(string $key): bool
    {
        return static::query()->where('key', $key)->exists();
    }

    public static function set(string $key, mixed $value, string $owner): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'owner' => $owner]);
    }

    public static function forget(string $key): int
    {
        return static::query()->where('key', $key)->delete();
    }
}
