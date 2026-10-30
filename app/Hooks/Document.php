<?php

namespace App\Hooks;

use App\Attributes\Action;
use App\Facades\Assets;
use App\Facades\Filter;

class Document
{
    #[Action('head')]
    public function title()
    {
        $separator = Filter::apply('title.separator', ' - ');
        $parts = Filter::apply('title.parts', [setting('site_name')]);
        $title = Filter::apply('title', implode($separator, $parts));

        echo "<title>{$title}</title>";
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

    #[Action('body')]
    #[Action('dashboard.body')]
    public function adminBar()
    {
        echo view('partials.admin-bar.admin-bar')->render();
    }
}
