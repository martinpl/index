<?php

namespace App\Foundation;

use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class Package
{
    abstract public function isActive(string $name): bool;

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

    public function delete(string $name): void
    {
        File::deleteDirectory($this->path($name));
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
}
