<?php

namespace App\Providers;

use App\Facades\Plugin as PluginFacade;
use App\Facades\Theme as ThemeFacade;
use App\Foundation\Plugin;
use App\Foundation\Theme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Plugin::class);
        $this->app->singleton(Theme::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PluginFacade::boot();
        ThemeFacade::boot();
    }
}
