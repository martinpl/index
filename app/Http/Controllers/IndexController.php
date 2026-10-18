<?php

namespace App\Http\Controllers;

use App\EntryTypes\Page;
use App\Facades\EntryType;
use App\Facades\Filter;
use App\Models\Entry;
use App\Models\Site;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Site $site, ?string $path = null): View
    {
        $path = trim(Str::after("/$path/", $site->path), '/');
        if (! $path) {
            $entry = Page::for('home');

            abort_unless($entry, 404);

            return $this->view('index', $entry);
        }

        $entry = Entry::where('slug', Str::afterLast($path, '/'))
            ->whereIn('type', EntryType::list()->keys())
            ->get()
            ->first(fn (Entry $entry): bool => $entry->path() === $path);

        abort_unless($entry, 404);

        return $this->view('index', $entry);
    }

    protected function view(string $view, Entry $entry): View
    {
        Filter::add('title.parts', fn ($parts) => [$entry->name, ...$parts]);

        return view($view, ['entry' => $entry]);
    }
}
