<?php

namespace App\Foundation;

use App\Models\Entry;
use App\Models\Meta;
use App\Models\Option;
use App\Models\Site;
use App\Models\Term;
use Composer\Autoload\ClassLoader;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class Package
{
    /** @var array<string, true> */
    private static array $autoloaded = [];

    protected bool $skipped = false;

    abstract public function isActive(string $name): bool;

    abstract public function deactivate(string $name): void;

    abstract protected function directory(): string;

    public function list(): Collection
    {
        if (! File::isDirectory($this->path())) {
            return collect();
        }

        return collect(File::directories($this->path()))
            ->mapWithKeys(function (string $path): array {
                $package = $this->manifest($path);
                $package['status'] = $this->isActive($package['slug']) ? 'active' : 'inactive';

                return [$package['slug'] => $package];
            })
            ->sortKeys();
    }

    public function isInstalled(string $name): bool
    {
        return $this->list()->has($name);
    }

    /**
     * Don't boot active packages, e.g. while their dependencies are being scoped.
     */
    public function skip(): void
    {
        $this->skipped = true;
    }

    public function delete(string $name): void
    {
        foreach (Site::all() as $site) {
            $site->execute(function () use ($name): void {
                if ($this->isActive($name)) {
                    $this->deactivate($name);
                }
            });
        }

        File::deleteDirectory($this->path($name));
    }

    /**
     * The owner key of the package's entries, terms, meta and options.
     */
    public function owner(string $name): string
    {
        return $this->directory().'/'.$name;
    }

    /**
     * Delete the package's data on every site.
     */
    public function purge(string $name): void
    {
        $owner = $this->owner($name);

        foreach (Site::all() as $site) {
            $site->execute(function () use ($owner): void {
                Entry::withAnyStatus()->ownedBy($owner)->lazyById()->each->forceDelete();
                Term::query()->ownedBy($owner)->lazyById()->each->delete();
                Meta::query()->ownedBy($owner)->delete();
                Option::query()->ownedBy($owner)->delete();
            });
        }
    }

    public function manifest(string $path): array
    {
        $manifest = json_decode(File::get($path.'/composer.json'), true);
        $slug = Str::afterLast($manifest['name'] ?? '', '/');
        if (! $slug) {
            throw new DomainException("Manifest [$path/composer.json] requires a package name.");
        }

        return [
            'slug' => $slug,
            'name' => $manifest['extra']['index']['name'] ?? Str::headline($slug),
            'version' => $manifest['version'] ?? null,
            'description' => $manifest['description'] ?? null,
        ];
    }

    public function path(?string $name = null): string
    {
        return base_path($this->directory()).($name ? '/'.$name : '');
    }

    public function storagePath(?string $name = null): string
    {
        return storage_path('app/'.$this->directory()).($name ? '/'.$name : '');
    }

    /**
     * Register the package's PSR-4 namespaces and its scoped dependencies.
     */
    protected function autoload(string $path): void
    {
        if (isset(self::$autoloaded[$path])) {
            return;
        }

        $manifest = json_decode(File::get($path.'/composer.json'), true);
        $loader = new ClassLoader;

        foreach ($manifest['autoload']['psr-4'] ?? [] as $namespace => $directories) {
            $loader->addPsr4($namespace, array_map(fn (string $directory): string => $path.'/'.$directory, (array) $directories));
        }

        $loader->register();

        if (File::isFile($path.'/vendor-prefixed/autoload.php')) {
            require_once $path.'/vendor-prefixed/autoload.php';
        }

        self::$autoloaded[$path] = true;
    }
}
