<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Site;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function __invoke(Site $site, ?string $path = null): View
    {
        $slug = trim(Str::after("/$path/", $site->path), '/');
        if ($slug == '') {
            return view('welcome');
        }

        return view('entry', [
            'entry' => Entry::where('slug', $slug)->firstOrFail(),
        ]);
    }
}
