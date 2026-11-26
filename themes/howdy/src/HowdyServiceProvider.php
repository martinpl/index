<?php

namespace Howdy;

use App\Entries\Page;
use App\Support\PackageServiceProvider;
use Howdy\Blocks\Footer;
use Howdy\Blocks\Navbar;

class HowdyServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        $this->addAsset('theme', 'resources/css/theme.css')->site()->editor();

        $this->addBlockTypes([Navbar::class, Footer::class]);

        $this->addLayout('default', [
            'name' => 'Default',
            'content' => fn (): array => [
                ['type' => Navbar::$type, 'data' => ['links' => [['label' => __('Home'), 'url' => '/']]]],
                ['type' => 'content'],
                ['type' => Footer::$type],
            ],
        ]);

        $this->addLayout('blank', [
            'name' => 'Blank',
            'content' => [['type' => 'content']],
        ]);

        $this->addPageTemplate(Page::NOT_FOUND, [
            'name' => 'Page not found',
            'content' => fn (): array => [
                ['type' => 'heading', 'data' => ['text' => __('Page not found'), 'level' => 'h1']],
                ['type' => 'paragraph', 'data' => ['text' => __('The page you are looking for does not exist or was moved.')]],
            ],
        ]);
    }
}
