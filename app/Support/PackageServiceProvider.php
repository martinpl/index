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
use App\Facades\Layouts;
use App\Facades\Metabox;
use App\Facades\PageTemplates;
use App\Facades\Settings;
use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Term;
use App\Schema\Component;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
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
     * Add a layout the site starts with, like a default one with a navbar and a footer or a blank one, keyed by its slug
     * and made of blocks placing the content of its entries in its content block. Users edit it among the layouts of
     * the site, which keeps the one it has when another package adds the same, and the `default` one shows the entries
     * that choose no other.
     *
     * @param  array{name?: string, content?: list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>|Closure(): list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>}  $config
     */
    protected function addLayout(string $key, array $config): void
    {
        Layouts::register($key, $config, static::owner());
    }

    /**
     * Add the template of the page shown for a page role, like `not_found` for the page shown when a page is not found,
     * until a published page is assigned to it. Users edit it in the templates tab of the pages in the dashboard, saving it as a page assigned
     * to the role, and get it back by resetting that page.
     *
     * @param  array{name?: string, slug?: string, content?: list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>|Closure(): list<array{type: string, data?: array<string, mixed>, attributes?: array<string, mixed>}>}  $config
     */
    protected function addPageTemplate(string $role, array $config): void
    {
        PageTemplates::register($role, $config, static::owner());
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
     * @param  array<string, array{title?: string, schema: Closure(): list<Component>, can?: string|null, position?: int}>  $pages
     */
    protected function addSettings(array $pages): void
    {
        foreach ($pages as $key => $config) {
            Settings::register($key, $config, static::owner());
        }
    }

    /**
     * @param  array{render?: Closure(mixed...): (View|Htmlable|string), schema?: Closure(): list<Component>, with?: array<string, mixed>, location?: string|list<string>, can?: string|null, position?: int}  $config
     */
    protected function addMetabox(string $key, array $config): void
    {
        Metabox::register($key, $config, static::owner());
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
