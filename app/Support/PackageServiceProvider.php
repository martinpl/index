<?php

namespace App\Support;

use App\Assets\Asset;
use App\Assets\Css;
use App\Assets\Js;
use App\Assets\Vite;
use App\Blocks\Block;
use App\Facades\Action;
use App\Facades\Assets;
use App\Facades\BlockType;
use App\Facades\EntryType;
use App\Facades\Filter;
use App\Facades\Settings;
use App\Facades\TermType;
use App\Fields\Field;
use App\Models\Entry;
use App\Models\Term;
use Closure;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;

abstract class PackageServiceProvider extends ServiceProvider
{
    /**
     * The owner key of the package, e.g. `plugins/blog`, derived from the provider's location.
     */
    public static function owner(): string
    {
        $file = (new ReflectionClass(static::class))->getFileName();

        return Str::of($file)->after(base_path().'/')->explode('/')->take(2)->implode('/');
    }

    /**
     * @param  list<class-string<Entry>>  $classes
     */
    protected function addEntryTypes(array $classes): void
    {
        foreach ($classes as $class) {
            EntryType::register($class, [], static::owner());
        }
    }

    /**
     * @param  list<class-string<Term>>  $classes
     */
    protected function addTermTypes(array $classes): void
    {
        foreach ($classes as $class) {
            TermType::register($class, [], static::owner());
        }
    }

    /**
     * @param  list<class-string<Block>>  $classes
     */
    protected function addBlockTypes(array $classes): void
    {
        foreach ($classes as $class) {
            BlockType::register($class, [], static::owner());
        }
    }

    /**
     * Load the asset, on the site unless told otherwise, telling its type by its path relative to the package:
     * a file in `public` is served as it is, as a stylesheet for `.css`, a script for `.js` or a module script for `.mjs`,
     * while any other path, or a list of them, is an entry point built by the package's own Vite.
     *
     * @param  string|list<string>  $path  Like `resources/css/app.css` or `public/js/menu.js`.
     */
    protected function addAsset(string $name, string|array $path): Asset
    {
        $file = is_string($path) && str_starts_with($path, 'public/') ? Str::after($path, 'public/') : null;

        $asset = match (true) {
            is_null($file) => Vite::make($name, $path),
            str_ends_with($file, '.css') => Css::make($name, $file),
            str_ends_with($file, '.js') => Js::make($name, $file),
            str_ends_with($file, '.mjs') => Js::make($name, $file)->module(),
            default => throw new InvalidArgumentException("Cannot tell the type of asset [$path] by its extension."),
        };

        Assets::register($asset, static::owner());

        return $asset;
    }

    /**
     * @param  array<string, array{title?: string, fields: Closure(): list<Field>, can?: string|null, position?: int}>  $pages
     */
    protected function addSettings(array $pages): void
    {
        foreach ($pages as $key => $config) {
            Settings::register($key, $config, static::owner());
        }
    }

    /**
     * @param  list<class-string>  $classes  Classes with `#[Action]` / `#[Filter]` methods.
     */
    protected function addHooks(array $classes): void
    {
        foreach ($classes as $class) {
            Action::subscribe($class);
            Filter::subscribe($class);
        }
    }
}
