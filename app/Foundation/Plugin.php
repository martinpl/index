<?php

namespace App\Foundation;

use App\Models\Setting;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

    /**
     * Call the static `bootstrap()` method of the service provider of every installed plugin declaring `extra.index.bootstrap`
     * in its manifest, active or not, while the app registers its services, before the site and its settings load. Plugins use
     * it for what must start before anything else, like a debugging tool seeing every query, and check whether they are
     * active on the site once the app has booted. Other plugins are not loaded, so their code doesn't run until activated.
     */
    public function bootstrap(): void
    {
        if ($this->skipped || ! File::isDirectory($this->path())) {
            return;
        }

        foreach (File::directories($this->path()) as $path) {
            if (! $this->hasBootstrap($path)) {
                continue;
            }

            $this->autoload($path);
            $class = $this->providerClass($path);

            if (class_exists($class) && method_exists($class, 'bootstrap')) {
                $class::bootstrap();
            }
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
        $class = $this->providerClass($path);

        if (! class_exists($class) || ! is_subclass_of($class, ServiceProvider::class)) {
            throw new DomainException("Plugin [$path] must define [$class] as a service provider.");
        }

        return $class;
    }

    private function hasBootstrap(string $path): bool
    {
        if (! File::isFile($path.'/composer.json')) {
            return false;
        }

        return (json_decode(File::get($path.'/composer.json'), true)['extra']['index']['bootstrap'] ?? false) === true;
    }

    private function providerClass(string $path): string
    {
        $slug = Str::studly($this->manifest($path)['slug']);

        return $slug.'\\'.$slug.'ServiceProvider';
    }
}
