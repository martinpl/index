<?php

namespace Howdy;

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
    }
}
