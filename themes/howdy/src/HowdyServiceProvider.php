<?php

namespace Howdy;

use App\Support\PackageServiceProvider;

class HowdyServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        $this->addAsset('theme', 'resources/css/theme.css')->site()->editor();
    }
}
