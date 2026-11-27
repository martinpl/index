<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Remembers the settings of each site for the lifetime of the request, loading every autoloaded
 * setting of the site with a single query on first use and any other setting on its own.
 */
class SettingCache
{
    /**
     * The known values keyed by settings table, then by key, where `null` marks a missing setting.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $values = [];

    /**
     * The settings tables whose autoloaded settings are loaded.
     *
     * @var array<string, true>
     */
    protected array $autoloaded = [];

    /**
     * Get the value of the setting, loading every autoloaded setting of the site on first use.
     */
    public function get(string $key): mixed
    {
        $this->autoload();

        return $this->find($key);
    }

    /**
     * Get the value of the setting alone, e.g. of another site than the one handling the request.
     */
    public function find(string $key): mixed
    {
        $table = $this->table();

        if (! array_key_exists($key, $this->values[$table] ?? [])) {
            $this->values[$table][$key] = Setting::query()->where('key', $key)->value('value');
        }

        return $this->values[$table][$key];
    }

    public function put(string $key, mixed $value): void
    {
        $this->values[$this->table()][$key] = $value;
    }

    public function forget(string $key): void
    {
        $this->put($key, null);
    }

    /**
     * Forget every known setting of the current site, e.g. after deleting many of them at once.
     */
    public function flush(): void
    {
        $table = $this->table();

        unset($this->values[$table], $this->autoloaded[$table]);
    }

    protected function autoload(): void
    {
        $table = $this->table();

        if (isset($this->autoloaded[$table])) {
            return;
        }

        $settings = Setting::query()->where('autoload', true)->pluck('value', 'key')->all();

        $this->values[$table] = array_replace($this->values[$table] ?? [], $settings);
        $this->autoloaded[$table] = true;
    }

    protected function table(): string
    {
        return (new Setting)->getTable();
    }
}
