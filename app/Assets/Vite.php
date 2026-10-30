<?php

namespace App\Assets;

use Illuminate\Foundation\Vite as LaravelVite;
use Illuminate\Support\Facades\Http;

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
     * The `.css` entry points inlined and scoped to the given selector, read from the build, or from the dev server
     * while it runs, with any other entry points loading as they are.
     */
    public function scopedTo(string $selector): string
    {
        $vite = $this->vite();
        [$styles, $scripts] = collect($this->entryPoints)->partition(fn (string $entryPoint): bool => str_ends_with($entryPoint, '.css'));

        $css = $styles->map(fn (string $entryPoint): string => $vite->isRunningHot()
            ? Http::get($vite->asset($entryPoint).'?direct')->throw()->body()
            : $vite->content($entryPoint)
        )->implode("\n");

        return "<style>@scope ($selector) {".$css.'}</style>'
            .($scripts->isEmpty() ? '' : $vite->withEntryPoints($scripts->values()->all())->toHtml());
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
