<?php

namespace App\Http\Controllers;

use App\Facades\EntryType;
use App\Models\Entry;
use App\Models\Option;
use App\Models\Site;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Site $site, ?string $path = null): View
    {
        $path = trim(Str::after("/$path/", $site->path), '/');
        if (! $path) {
            return view('index', ['entry' => Entry::findOrFail(Option::get('home_entry'))]);
        }

        $entry = Entry::where('slug', Str::afterLast($path, '/'))
            ->whereIn('type', EntryType::list()->keys())
            ->get()
            ->first(fn (Entry $entry): bool => $entry->path() === $path);

        abort_unless($entry, 404);

        return view('index', ['entry' => $entry]);
    }
}
