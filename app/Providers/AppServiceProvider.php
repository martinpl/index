<?php

namespace App\Providers;

use App\EntryTypes\Page;
use App\Facades\Action as ActionFacade;
use App\Facades\EntryType as EntryTypeFacade;
use App\Facades\Settings as SettingsFacade;
use App\Fields\Text;
use App\Foundation\Action;
use App\Foundation\AdminBar;
use App\Foundation\DashboardMenu;
use App\Foundation\EntryType;
use App\Foundation\Filter;
use App\Foundation\Plugin;
use App\Foundation\Role;
use App\Foundation\Settings;
use App\Foundation\TermType;
use App\Foundation\Theme;
use App\Hooks\Document;
use App\Hooks\Menus;
use App\Models\Entry;
use App\Models\Site;
use App\Models\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Application as Artisan;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        $this->app->singleton(Role::class);
        $this->app->singleton(Action::class);
        $this->app->singleton(Filter::class);
        $this->app->singleton(AdminBar::class);
        $this->app->singleton(DashboardMenu::class);
        $this->app->singleton(Settings::class);
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

        EntryTypeFacade::register(Page::class, [], 'core');

        SettingsFacade::register('general', [
            'title' => 'General',
            'can' => 'manage-settings',
            'position' => 0,
            'fields' => fn (): array => [
                Text::make('site_name')->label(__('Site name'))->required()->rules('max:255'),
            ],
        ], 'core');

        Blade::directive('action', fn (string $expression): string => "<?php \\App\\Facades\\Action::run($expression); ?>");
        Blade::directive('filter', fn (string $expression): string => "<?php echo \\App\\Facades\\Filter::apply($expression); ?>");

        ActionFacade::subscribe(Document::class);
        ActionFacade::subscribe(Menus::class);

        Gate::before(fn (User $user, string $ability): ?bool => $user->hasAbility($ability) ?: null);

        if ($this->app->runningInConsole()) {
            $this->bootConsoleSite();
        } else {
            $this->bootRequestSite();
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
     * Make the site given by the "--site" option, or the main site, current,
     * without booting plugins or the theme when "--skip-plugins" or "--skip-themes" is given.
     */
    protected function bootConsoleSite(): void
    {
        Artisan::starting(function (Artisan $artisan): void {
            $artisan->getDefinition()->addOptions([
                new InputOption('site', null, InputOption::VALUE_REQUIRED, 'The ID of the site the command should run on'),
                new InputOption('skip-plugins', null, InputOption::VALUE_NONE, 'Do not boot active plugins'),
                new InputOption('skip-themes', null, InputOption::VALUE_NONE, 'Do not boot the active theme'),
            ]);
        });

        $input = new ArgvInput;
        if ($input->hasParameterOption('--skip-plugins')) {
            $this->app->make(Plugin::class)->skip();
        }
        if ($input->hasParameterOption('--skip-themes')) {
            $this->app->make(Theme::class)->skip();
        }

        if ($site = $input->getParameterOption('--site')) {
            Site::findOrFail($site)->makeCurrent();
        } elseif (rescue(fn () => Schema::hasTable('sites'), false, false)) {
            Site::find(1)?->makeCurrent();
        }
    }

    /**
     * Make the site matching the request current, before any middleware runs.
     */
    protected function bootRequestSite(): void
    {
        $site = Site::findForRequest($this->app['request']);

        abort_unless($site, 404);

        $site->makeCurrent();
    }
}
