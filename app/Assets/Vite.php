<?php

namespace App\Assets;

use Illuminate\Foundation\Vite as LaravelVite;

/**
 * Entry points built by the package's own Vite, with the `build` directory and `hot` file
 * that `laravel-vite-plugin` creates in the package's `public` directory by default.
 */
class Vite extends Asset
{
    /**
     * @var list<string>
     */
    protected array $entryPoints = [];

    /**
     * @param  string|list<string>  $entryPoints  Relative to the package, like `resources/css/app.css`.
     */
    public static function make(string $name, string|array $entryPoints): static
    {
        $asset = new static($name, 'build');
        $asset->entryPoints = (array) $entryPoints;

        return $asset;
    }

    /**
     * The app's Vite, keeping its configuration like the CSP nonce, pointed at the package's build.
     */
    protected function vite(): LaravelVite
    {
        return (clone app(LaravelVite::class))
            ->useHotFile(public_path(dirname($this->publicPath()).'/hot'))
            ->useBuildDirectory($this->publicPath());
    }

    public function toHtml(): string
    {
        return $this->vite()->withEntryPoints($this->entryPoints)->toHtml();
    }
}
