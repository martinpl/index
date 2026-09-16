<?php

namespace App\Providers;

use App\Foundation\Plugin;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Plugin::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Plugin $plugins): void
    {
        $plugins->boot();
    }
}
