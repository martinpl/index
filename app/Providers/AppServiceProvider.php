<?php

namespace App\Providers;

use App\Facades\EntryType as EntryTypeFacade;
use App\Facades\Plugin as PluginFacade;
use App\Facades\TermType as TermTypeFacade;
use App\Facades\Theme as ThemeFacade;
use App\Foundation\EntryType;
use App\Foundation\Plugin;
use App\Foundation\TermType;
use App\Foundation\Theme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EntryType::class);
        $this->app->singleton(TermType::class);
        $this->app->singleton(Plugin::class);
        $this->app->singleton(Theme::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        EntryTypeFacade::register('page');
        EntryTypeFacade::register('post');
        TermTypeFacade::register('category', ['entry_types' => ['post']]);
        TermTypeFacade::register('tag', ['entry_types' => ['post']]);

        PluginFacade::boot();
        ThemeFacade::boot();
    }
}
