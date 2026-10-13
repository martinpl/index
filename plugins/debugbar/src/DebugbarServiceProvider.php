<?php

namespace Debugbar;

use App\Support\PackageServiceProvider;

class DebugbarServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        $this->app->register(Vendor\Fruitcake\LaravelDebugbar\ServiceProvider::class);
        config(['debugbar.storage.enabled' => false]);
    }
}
