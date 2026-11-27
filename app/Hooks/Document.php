<?php

namespace App\Hooks;

use App\Attributes\Action;
use App\Facades\Assets;
use App\Facades\Filter;

class Document
{
    /**
     * Print the title of the page, escaped, as its parts come from the site name, the entry and plugins.
     */
    #[Action('head')]
    public function title(): void
    {
        $separator = Filter::apply('title.separator', ' - ');
        $parts = Filter::apply('title.parts', [setting('site_name')]);
        $title = Filter::apply('title', implode($separator, $parts));

        echo '<title>'.e($title).'</title>';
    }

    #[Action('head')]
    public function siteAssets(): void
    {
        echo Assets::render('site');
    }

    #[Action('dashboard.head')]
    public function dashboardAssets(): void
    {
        echo Assets::render('dashboard');
    }

    #[Action(['body.open', 'dashboard.body.open'])]
    public function adminBar(): void
    {
        if (auth()->guest()) {
            return;
        }

        echo view('partials.admin-bar.admin-bar')->render();
    }
}
