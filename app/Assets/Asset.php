<?php

namespace App\Assets;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

abstract class Asset implements Htmlable
{
    use Conditionable, Macroable;

    /**
     * The package the asset belongs to, e.g. `core` or `themes/index`, set when it is registered.
     */
    public string $owner = 'core';

    /**
     * @var list<string>
     */
    protected array $contexts = [];

    /**
     * @param  string  $path  Relative to the package's `public` directory, or to the app's for the `core` owner.
     */
    final public function __construct(public string $name, public string $path) {}

    public static function make(string $name, string $path): static
    {
        return new static($name, $path);
    }

    /**
     * Load the asset on the site, which is where assets load unless told otherwise.
     */
    public function site(): static
    {
        return $this->on('site');
    }

    /**
     * Load the asset in the dashboard.
     */
    public function dashboard(): static
    {
        return $this->on('dashboard');
    }

    /**
     * Load the asset in the block editor, where stylesheets are scoped to its preview so blocks look there as they do on the site.
     */
    public function editor(): static
    {
        return $this->on('editor');
    }

    /**
     * @return list<string>
     */
    public function getContexts(): array
    {
        return $this->contexts ?: ['site'];
    }

    public function loadsOn(string $context): bool
    {
        return in_array($context, $this->getContexts(), true);
    }

    /**
     * The HTML loading the asset with its styles scoped to the given selector, like the block editor's preview,
     * which loads assets without styles as they are.
     */
    public function scopedTo(string $selector): string
    {
        return $this->toHtml();
    }

    /**
     * The key of the asset, unique across packages, like `themes/index:theme`.
     */
    public function key(): string
    {
        return "$this->owner:$this->name";
    }

    /**
     * The URL of the asset, versioned by when the file last changed so browsers fetch it again.
     */
    public function url(): string
    {
        $url = asset($this->publicPath());

        return File::exists($path = public_path($this->publicPath())) ? $url.'?v='.File::lastModified($path) : $url;
    }

    /**
     * The asset's path in the app's `public` directory, where packages are linked as `{plugins|themes}/{name}`.
     */
    protected function publicPath(): string
    {
        return ($this->owner === 'core' ? '' : $this->owner.'/').ltrim($this->path, '/');
    }

    protected function on(string $context): static
    {
        $this->contexts = array_values(array_unique([...$this->contexts, $context]));

        return $this;
    }
}
