<?php

namespace App\Foundation;

use App\Models\Option;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

// TODO: Perfs we could cache list() in memory and use that in helpers
// TODO: Value object for plugin?
class Plugin
{
    private const activePlugins = 'active_plugins';

    public function boot(): void
    {
        $hasDb = rescue(fn () => DB::connection()->getPdo(), false);
        if (! $hasDb || ! Schema::hasTable('options')) {
            return;
        }

        foreach ($this->active() as $name) {
            app()->register($this->pluginClass($this->path($name)));
        }
    }

    public function list(): Collection
    {
        if (! File::isDirectory($this->path())) {
            return collect();
        }

        $active = $this->active();

        return collect(File::directories($this->path()))
            ->map(function (string $path) use ($active): array {
                $plugin = $this->manifest($path);
                $plugin['status'] = in_array($plugin['slug'], $active) ? 'active' : 'inactive';

                return [$plugin['slug'] => $plugin];
            })
            ->collapse()
            ->sortKeys();
    }

    public function isInstalled(string $name): bool
    {
        return $this->list()->has($name);
    }

    public function isActive(string $name): bool
    {
        return in_array($name, $this->active());
    }

    public function activate(string $name): void
    {
        Option::set(self::activePlugins, array_unique([...$this->active(), $name]));
    }

    public function deactivate(string $name): void
    {
        Option::set(self::activePlugins, array_diff($this->active(), [$name]));
    }

    public function delete(string $name): void
    {
        File::deleteDirectory($this->path($name));
    }

    private function active(): array
    {
        return Option::get(self::activePlugins, []);
    }

    public function manifest(string $path): array
    {
        $manifest = json_decode(File::get($path.'/composer.json'), true);
        $slug = Str::afterLast($manifest['name'] ?? '', '/');
        if (! $slug) {
            throw new DomainException("Plugin [$path/composer.json] requires a package name.");
        }

        return [
            'slug' => $slug,
            'name' => $manifest['extra']['index']['name'] ?? Str::headline($slug),
            'version' => $manifest['version'] ?? null,
            'description' => $manifest['description'] ?? null,
        ];
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

    private function path(?string $name = null): string
    {
        return base_path('plugins').($name ? '/'.$name : '');
    }
}
