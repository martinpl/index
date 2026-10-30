<?php

namespace Index;

use App\Support\PackageServiceProvider;

class IndexServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        $this->addAsset('theme', 'resources/css/theme.css');
        $this->addAsset('blocks', 'resources/css/blocks.css')->site()->editor();
    }
}
