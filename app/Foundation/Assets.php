<?php

namespace App\Foundation;

use App\Assets\Asset;
use App\Facades\Filter;
use DomainException;
use Illuminate\Support\Collection;

class Assets
{
    /**
     * @var array<string, Asset>
     */
    protected array $assets = [];

    /**
     * @param  string  $owner  Same format as the data owner column, e.g. `core` or `plugins/blog`.
     */
    public function register(Asset $asset, string $owner): void
    {
        $asset->owner = $owner;

        if ($this->has($key = $asset->key())) {
            throw new DomainException("Asset [$key] is already registered.");
        }

        $this->assets[$key] = $asset;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->assets);
    }

    public function get(string $key): ?Asset
    {
        return $this->assets[$key] ?? null;
    }

    /**
     * The assets loading on the given context, `site`, `dashboard` or `editor`, in the order they were registered,
     * which plugins may change through the `assets` filter.
     *
     * @return Collection<string, Asset>
     */
    public function list(string $context): Collection
    {
        $assets = collect($this->assets)->filter(fn (Asset $asset): bool => $asset->loadsOn($context));

        return collect(Filter::apply('assets', $assets->all(), $context));
    }

    /**
     * The HTML loading the assets of the given context, with the editor's stylesheets scoped to its preview.
     */
    public function render(string $context): string
    {
        return $this->list($context)
            ->map(fn (Asset $asset): string => $context === 'editor' ? $asset->scopedTo('.block-preview') : $asset->toHtml())
            ->implode("\n");
    }
}
