<?php

namespace App\Http\Controllers;

use App\Entries\Page;
use App\Facades\EntryType;
use App\Facades\Filter;
use App\Facades\Theme;
use App\Models\Entry;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class IndexController extends Controller
{
    /**
     * Show the published entry at the path, or the page shown when a page is not found, with a not found status,
     * like at the path of that page itself.
     */
    public function __invoke(?string $path = null): Response
    {
        abort_if(Theme::isHeadless(), 404);

        $entry = $this->findEntry(trim($path ?? '', '/'));

        if ($entry && ! ($entry instanceof Page && $entry->role() === Page::NOT_FOUND)) {
            return $this->show($entry);
        }

        $notFound = Page::shownFor(Page::NOT_FOUND);

        abort_if(is_null($notFound), 404);

        return $this->show($notFound, 404);
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

    /**
     * Show the entry as the one the site is showing, titled by its name.
     */
    protected function show(Entry $entry, int $status = 200): Response
    {
        app()->instance(Entry::class, $entry);

        Filter::add('title.parts', fn (array $parts): array => [$entry->name, ...$parts]);

        return response()->view('index', ['entry' => $entry], $status);
    }
}
