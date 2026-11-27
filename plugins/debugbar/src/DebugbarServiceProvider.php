<?php

namespace Debugbar;

use App\Support\PackageServiceProvider;
use Debugbar\Vendor\Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Debugbar\Vendor\Fruitcake\LaravelDebugbar\ServiceProvider;

class DebugbarServiceProvider extends PackageServiceProvider
{
    public static function bootstrap(): void
    {
        config(['debugbar.storage.enabled' => false]);
        app()->register(ServiceProvider::class);
        app()->booted(function (): void {
            if (! app()->getProvider(static::class)) {
                app(LaravelDebugbar::class)->disable();
            }
        });
    }
}
