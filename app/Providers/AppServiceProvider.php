<?php

namespace App\Providers;

use App\EntryTypes\Page;
use App\Facades\EntryType as EntryTypeFacade;
use App\Foundation\EntryType;
use App\Foundation\Plugin;
use App\Foundation\TermType;
use App\Foundation\Theme;
use App\Models\Entry;
use App\Models\Site;
use App\Models\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Application as Artisan;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
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
        $this->configureDefaults();

        Relation::enforceMorphMap([
            'entry' => Entry::class,
            'term' => Term::class,
            'user' => User::class,
        ]);

        EntryTypeFacade::register(Page::class);

        if ($this->app->runningInConsole()) {
            $this->bootConsoleSite();
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
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
