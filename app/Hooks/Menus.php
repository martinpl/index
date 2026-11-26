<?php

namespace App\Hooks;

use App\Attributes\Action;
use App\Entries\Layout;
use App\Facades\EntryType;
use App\Facades\Settings;
use App\Facades\TermType;
use App\Foundation\AdminBar;
use App\Foundation\DashboardMenu;
use App\Models\Entry;
use App\Models\Site;
use App\Models\User;
use App\Schema\Component;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class Menus
{
    #[Action('admin-bar.menu')]
    public function adminBar(AdminBar $bar): void
    {
        $this->sites($bar);
        $this->site($bar);
        $this->new($bar);
        $this->edit($bar);
        $this->account($bar);
    }

    /**
     * Add the menu of the site, linking to its home page from the dashboard, to the dashboard from the site, and to the pages
     * managing the site.
     */
    protected function site(AdminBar $bar): void
    {
        $inDashboard = fn (): bool => request()->routeIs('dashboard.*', 'profile.*', 'security.*');

        $bar->add('site', [
            'title' => setting('site_name'),
            'icon' => 'app-logo-icon',
            'position' => 5,
        ]);
        $bar->add('dashboard', [
            'title' => __('Dashboard'),
            'url' => route('dashboard.home'),
            'icon' => 'lucide-layout-dashboard',
            'parent' => 'site',
            'position' => 0,
            'can' => fn (): bool => ! $inDashboard(),
        ]);
        $bar->add('home', [
            'title' => __('Home'),
            'url' => fn (): ?string => page_url('home'),
            'icon' => 'lucide-home',
            'parent' => 'site',
            'position' => 0,
            'can' => $inDashboard,
        ]);
        $bar->add('plugins', [
            'title' => __('Plugins'),
            'url' => route('dashboard.plugins'),
            'icon' => 'lucide-plug',
            'parent' => 'site',
            'position' => 10,
            'can' => 'plugins.manage',
        ]);
        $bar->add('themes', [
            'title' => __('Themes'),
            'url' => route('dashboard.themes'),
            'icon' => 'lucide-palette',
            'parent' => 'site',
            'position' => 20,
            'can' => 'themes.manage',
        ]);
        $bar->add('settings', [
            'title' => __('Settings'),
            'url' => fn (): string => route('dashboard.settings', $this->settingsPages()->first()['key']),
            'icon' => 'lucide-settings',
            'parent' => 'site',
            'position' => 30,
            'can' => fn (): bool => $this->settingsPages()->isNotEmpty(),
        ]);
    }

    /**
     * Add the menu adding an entry of each type, and a user, for users who may manage them.
     */
    protected function new(AdminBar $bar): void
    {
        $entryTypes = EntryType::list();

        $bar->add('new', [
            'title' => __('New'),
            'icon' => 'lucide-plus',
            'position' => 10,
            'can' => fn (): bool => Gate::allows('users.manage') || $entryTypes->contains(fn (array $entryType): bool => Gate::allows($entryType['can'])),
        ]);

        foreach ($entryTypes as $entryType) {
            $bar->add("new.{$entryType['key']}", [
                'title' => __($entryType['name']),
                'url' => route('dashboard.entries.create', $entryType['key']),
                'icon' => $entryType['icon'],
                'parent' => 'new',
                'position' => ($entryType['menu_parent'] ? 1000 : 0) + $entryType['menu_position'],
                'can' => $entryType['can'],
            ]);
        }

        $bar->add('new.user', [
            'title' => __('User'),
            'url' => route('dashboard.users.create'),
            'icon' => 'lucide-user',
            'parent' => 'new',
            'position' => 2000,
            'can' => 'users.manage',
        ]);
    }

    /**
     * Add the links editing the entry the site is showing and its layout, for users who may manage them.
     */
    protected function edit(AdminBar $bar): void
    {
        $bar->add('edit', [
            'title' => fn (): string => __('Edit :type', ['type' => __(EntryType::get(Entry::current()->type)['name'])]),
            'url' => fn (): string => Entry::current()->editUrl(),
            'icon' => 'lucide-pencil',
            'position' => 20,
            'can' => fn (): bool => ($entry = Entry::current()) && Gate::allows(EntryType::get($entry->type)['can']),
        ]);
        $bar->add('edit-layout', [
            'title' => __('Edit Layout'),
            'url' => fn (): string => Entry::current()->layout()->editUrl(),
            'icon' => 'lucide-layout-template',
            'position' => 30,
            'can' => fn (): bool => Entry::current()?->layout() && Gate::allows(EntryType::get(Layout::$type)['can']),
        ]);
    }

    /**
     * Add the menu of the user's account, with their profile settings and logging out.
     */
    protected function account(AdminBar $bar): void
    {
        $bar->add('account', [
            'title' => fn (): View => view('partials.admin-bar.account', ['user' => Auth::user()]),
            'align' => 'end',
        ]);
        $bar->add('user-info', [
            'title' => fn (): View => view('partials.admin-bar.user-info', ['user' => Auth::user()]),
            'parent' => 'account',
            'position' => 0,
        ]);
        $bar->add('user-settings', [
            'title' => __('Settings'),
            'url' => route('profile.edit'),
            'icon' => 'lucide-settings',
            'parent' => 'account',
        ]);
        $bar->add('logout', [
            'title' => fn (): View => view('partials.admin-bar.logout'),
            'parent' => 'account',
            'position' => 100,
        ]);
    }

    /**
     * Add the menu switching between the sites, with their dashboards and front pages, for users managing them when there are several.
     */
    protected function sites(AdminBar $bar): void
    {
        $sites = Site::query()->orderBy('id')->get();

        if ($sites->count() < 2) {
            return;
        }

        $bar->add('my-sites', [
            'label' => __('My Sites'),
            'icon' => 'lucide-network',
            'position' => 0,
            'can' => fn (?User $user): bool => (bool) $user?->canManageSites(),
        ]);

        foreach ($sites as $site) {
            $bar->add("my-sites.{$site->id}", [
                'title' => fn (): string => $site->setting('site_name', $site->domain),
                'icon' => 'app-logo-icon',
                'parent' => 'my-sites',
                'position' => $site->id,
            ]);
            $bar->add("my-sites.{$site->id}.dashboard", [
                'title' => __('Dashboard'),
                'url' => $site->url('dashboard'),
                'icon' => 'lucide-layout-dashboard',
                'parent' => "my-sites.{$site->id}",
            ]);
            $bar->add("my-sites.{$site->id}.visit", [
                'title' => __('Visit'),
                'url' => $site->url(),
                'icon' => 'lucide-external-link',
                'parent' => "my-sites.{$site->id}",
            ]);
        }

        $bar->add('my-sites.manage', [
            'title' => __('Manage Sites'),
            'url' => $sites->first()->url('dashboard/sites'),
            'icon' => 'lucide-settings',
            'parent' => 'my-sites',
            'position' => $sites->last()->id + 1,
        ]);
    }

    #[Action('dashboard.menu')]
    public function dashboard(DashboardMenu $menu): void
    {
        $menu->add('dashboard', [
            'title' => __('Dashboard'),
            'url' => route('dashboard.home'),
            'icon' => 'lucide-layout-dashboard',
            'position' => 0,
        ]);

        foreach (EntryType::list() as $entryType) {
            $menu->add("entries.{$entryType['key']}", [
                'title' => __(Str::plural($entryType['name'])),
                'url' => route('dashboard.entries', $entryType['key']),
                'icon' => $entryType['icon'],
                'parent' => $entryType['menu_parent'],
                'position' => $entryType['menu_position'],
                'can' => $entryType['can'],
            ]);

            // The sidebar nests one level, so an entry type under another item links only to its listing.
            if (! $entryType['menu_parent']) {
                $menu->add("entries.{$entryType['key']}.all", [
                    'title' => __('All :types', ['types' => __(Str::plural($entryType['name']))]),
                    'url' => route('dashboard.entries', $entryType['key']),
                    'parent' => "entries.{$entryType['key']}",
                    'position' => 0,
                    'can' => $entryType['can'],
                ]);
                $menu->add("entries.{$entryType['key']}.create", [
                    'title' => __('Add :type', ['type' => __($entryType['name'])]),
                    'url' => route('dashboard.entries.create', $entryType['key']),
                    'parent' => "entries.{$entryType['key']}",
                    'position' => 1,
                    'can' => $entryType['can'],
                ]);
            }

            foreach (TermType::list()->filter(fn (array $termType): bool => in_array($entryType['key'], $termType['entry_types'])) as $termType) {
                $menu->add("entries.{$entryType['key']}.{$termType['key']}", [
                    'title' => __(Str::plural($termType['name'])),
                    'url' => route('dashboard.terms', $termType['key']),
                    'parent' => "entries.{$entryType['key']}",
                    'can' => $termType['can'],
                ]);
            }
        }

        $menu->add('appearance', [
            'title' => __('Appearance'),
            'icon' => 'lucide-paintbrush',
            'position' => 70,
            'can' => 'themes.manage',
        ]);
        $menu->add('appearance.themes', [
            'title' => __('Themes'),
            'url' => route('dashboard.themes'),
            'icon' => 'lucide-palette',
            'parent' => 'appearance',
            'position' => 0,
            'can' => 'themes.manage',
        ]);

        $menu->add('plugins', [
            'title' => __('Plugins'),
            'url' => route('dashboard.plugins'),
            'icon' => 'lucide-plug',
            'position' => 80,
            'can' => 'plugins.manage',
        ]);

        $menu->add('users', [
            'title' => __('Users'),
            'url' => route('dashboard.users'),
            'icon' => 'lucide-users',
            'position' => 90,
            'can' => 'users.manage',
        ]);
        $menu->add('users.all', [
            'title' => __('All Users'),
            'url' => route('dashboard.users'),
            'parent' => 'users',
            'position' => 0,
            'can' => 'users.manage',
        ]);
        $menu->add('users.create', [
            'title' => __('Add User'),
            'url' => route('dashboard.users.create'),
            'parent' => 'users',
            'position' => 1,
            'can' => 'users.manage',
        ]);

        $pages = $this->settingsPages();

        if ($pages->isNotEmpty()) {
            $menu->add('settings', [
                'title' => __('Settings'),
                'icon' => 'lucide-settings',
                'position' => 100,
            ]);
        }

        foreach ($pages as $page) {
            $menu->add("settings.{$page['key']}", [
                'title' => __($page['title']),
                'url' => route('dashboard.settings', $page['key']),
                'parent' => $page['parent'],
                'position' => $page['position'],
            ]);
        }
    }

    /**
     * The settings pages the current user may change.
     *
     * @return Collection<string, array{key: string, title: string, page_title: string, schema: Closure(): list<Component>, can: string|null, parent: string, position: int, owner: string}>
     */
    protected function settingsPages(): Collection
    {
        return Settings::list()->filter(fn (array $page): bool => Settings::allows($page['key']));
    }
}
