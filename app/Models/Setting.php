<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasOwner;
use App\Support\SettingCache;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'owner', 'autoload'])]
class Setting extends Model
{
    use BelongsToSite;

    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    use HasOwner;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => app(SettingCache::class)->put($setting->key, $setting->value));
        static::deleted(fn (Setting $setting) => app(SettingCache::class)->forget($setting->key));
    }

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'autoload' => 'boolean',
        ];
    }

    /**
     * Get the value of the setting, remembered for the rest of the request. Autoloaded settings
     * of the site are all loaded on first use, others are loaded when asked for.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return app(SettingCache::class)->get($key) ?? $default;
    }

    public static function exists(string $key): bool
    {
        return app(SettingCache::class)->get($key) !== null;
    }

    /**
     * Store the setting, or forget it when the value is empty.
     *
     * @param  bool|null  $autoload  Whether to load the setting with the others on every request. Settings read on most requests should be autoloaded, large or rarely read ones should not. Null keeps the current choice, not autoloading new settings.
     */
    public static function set(string $key, mixed $value, string $owner, ?bool $autoload = null): void
    {
        if ($value === null || $value === []) {
            self::forget($key);

            return;
        }

        static::query()->updateOrCreate(['key' => $key], array_filter(
            ['value' => $value, 'owner' => $owner, 'autoload' => $autoload],
            fn (mixed $attribute): bool => $attribute !== null,
        ));
    }

    public static function forget(string $key): int
    {
        app(SettingCache::class)->forget($key);

        return static::query()->where('key', $key)->delete();
    }
}
