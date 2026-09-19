<?php

namespace App\Foundation;

use App\Models\Option;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class Theme extends Package
{
    private const activeTheme = 'active_theme';

    public function boot(): void
    {
        $hasDb = rescue(fn () => DB::connection()->getPdo(), false);
        if (! $hasDb || ! Schema::hasTable('options')) {
            return;
        }

        $name = $this->active();
        if (! $name) {
            return;
        }

        app()->register($this->themeClass($this->path($name)));
    }

    public function isActive(string $name): bool
    {
        return $this->active() === $name;
    }

    public function activate(string $name): void
    {
        Option::set(self::activeTheme, $name);
    }

    public function deactivate(string $name): void
    {
        if ($this->isActive($name)) {
            Option::forget(self::activeTheme);
        }
    }

    protected function directory(): string
    {
        return 'themes';
    }

    private function active(): ?string
    {
        return Option::get(self::activeTheme);
    }

    private function themeClass(string $path): string
    {
        $slug = $this->manifest($path)['slug'];
        $class = Str::studly($slug).'\\'.Str::studly($slug).'ServiceProvider';

        if (! class_exists($class) || ! is_subclass_of($class, ServiceProvider::class)) {
            throw new DomainException("Theme [$path] must define [$class] as a service provider.");
        }

        return $class;
    }
}
