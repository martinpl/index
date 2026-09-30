<?php

namespace App\Providers;

use App\Facades\EntryType as EntryTypeFacade;
use App\Facades\TermType as TermTypeFacade;
use App\Foundation\EntryType;
use App\Foundation\Plugin;
use App\Foundation\TermType;
use App\Foundation\Theme;
use App\Models\Entry;
use App\Models\Site;
use App\Models\Term;
use Illuminate\Console\Application as Artisan;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;

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
        Relation::enforceMorphMap([
            'entry' => Entry::class,
            'term' => Term::class,
        ]);

        EntryTypeFacade::register('page', ['slug' => '']);
        EntryTypeFacade::register('post');
        TermTypeFacade::register('category', ['entry_types' => ['post']]);
        TermTypeFacade::register('tag', ['entry_types' => ['post']]);

        if ($this->app->runningInConsole()) {
            $this->bootConsoleSite();
        }
    }

    /**
     * Make the site given by the "--site" option, or the main site, current.
     */
    protected function bootConsoleSite(): void
    {
        Artisan::starting(function (Artisan $artisan): void {
            $artisan->getDefinition()->addOption(
                new InputOption('site', null, InputOption::VALUE_REQUIRED, 'The ID of the site the command should run on'),
            );
        });

        if ($site = (new ArgvInput)->getParameterOption('--site')) {
            Site::findOrFail($site)->makeCurrent();
        } elseif (rescue(fn () => Schema::hasTable('sites'), false, false)) {
            Site::find(1)?->makeCurrent();
        }
    }
}
