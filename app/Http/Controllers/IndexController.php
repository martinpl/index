<?php

namespace App\Http\Controllers;

use App\EntryTypes\Page;
use App\Facades\EntryType;
use App\Facades\Filter;
use App\Facades\Theme;
use App\Models\Entry;
use App\Models\Site;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Site $site, ?string $path = null): View
    {
        abort_if(Theme::isHeadless(), 404);

        $entry = $this->findEntry(trim(Str::after("/$path/", $site->path), '/'));

        abort_unless($entry, 404);

        Filter::add('title.parts', fn ($parts) => [$entry->name, ...$parts]);

        return view('index', ['entry' => $entry]);
    }

    /**
     * Find the published entry at the given path, the home page for an empty one.
     */
    protected function findEntry(string $path): ?Entry
    {
        if (! $path) {
            return Page::for('home');
        }

        return Entry::where('slug', Str::afterLast($path, '/'))
            ->whereIn('type', EntryType::list()->keys())
            ->get()
            ->first(fn (Entry $entry): bool => $entry->path() === $path);
    }
}
