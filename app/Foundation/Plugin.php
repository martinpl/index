<?php

namespace App\Foundation;

use App\Models\Setting;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

// TODO: Perfs we could cache list() in memory and use that in helpers
// TODO: Value object for plugin?
class Plugin extends Package
{
    private const activePlugins = 'active_plugins';

    public function boot(): void
    {
        $hasDb = rescue(fn () => DB::connection()->getPdo(), false);
        if ($this->skipped || ! $hasDb || ! Schema::hasTable('settings')) {
            return;
        }

        foreach ($this->active() as $name) {
            $path = $this->path($name);
            $this->autoload($path);

            app()->register($this->pluginClass($path));
        }
    }

    public function isActive(string $name): bool
    {
        return in_array($name, $this->active());
    }

    public function activate(string $name): void
    {
        Setting::set(self::activePlugins, array_unique([...$this->active(), $name]), 'core');
    }

    public function deactivate(string $name): void
    {
        Setting::set(self::activePlugins, array_diff($this->active(), [$name]), 'core');
    }

    protected function directory(): string
    {
        return 'plugins';
    }

    private function active(): array
    {
        return Setting::get(self::activePlugins, []);
    }

    private function pluginClass(string $path): string
    {
        $slug = $this->manifest($path)['slug'];
        $class = Str::studly($slug).'\\'.Str::studly($slug).'ServiceProvider';

        if (! class_exists($class) || ! is_subclass_of($class, ServiceProvider::class)) {
            throw new DomainException("Plugin [$path] must define [$class] as a service provider.");
        }

        return $class;
    }
}
