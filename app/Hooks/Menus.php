<?php

namespace App\Hooks;

use App\Attributes\Action;
use App\Facades\EntryType;
use App\Facades\Settings;
use App\Facades\TermType;
use App\Foundation\AdminBar;
use App\Foundation\DashboardMenu;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Menus
{
    #[Action('admin-bar.menu')]
    public function adminBar(AdminBar $bar): void
    {
        $inDashboard = fn (): bool => request()->routeIs('dashboard.*', 'profile.*', 'security.*');

        $bar->add('site', [
            'title' => setting('site_name'),
            'icon' => 'app-logo-icon',
            'position' => 0,
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

        $bar->add('account', [
            'title' => fn (): View => view('partials.admin-bar.account', ['user' => Auth::user()]),
            'align' => 'end',
        ]);
        $bar->add('user-info', [
            'title' => fn (): View => view('partials.admin-bar.user-info', ['user' => Auth::user()]),
            'parent' => 'account',
            'position' => 0,
        ]);
        $bar->add('settings', [
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
                'can' => $entryType['can'],
            ]);

            foreach (TermType::list()->filter(fn (array $termType): bool => in_array($entryType['key'], $termType['entry_types'])) as $termType) {
                $menu->add("entries.{$entryType['key']}.{$termType['key']}", [
                    'title' => __(Str::plural($termType['name'])),
                    'url' => route('dashboard.terms', $termType['key']),
                    'parent' => "entries.{$entryType['key']}",
                ]);
            }
        }

        $menu->add('plugins', [
            'title' => __('Plugins'),
            'url' => route('dashboard.plugins'),
            'icon' => 'lucide-plug',
            'position' => 90,
            'can' => 'plugins.manage',
        ]);

        $pages = Settings::list()->filter(fn (array $page): bool => Settings::allows($page['key']));

        if ($pages->isNotEmpty()) {
            $menu->add('settings', [
                'title' => __('Settings'),
                'url' => route('dashboard.settings', $pages->first()['key']),
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
}
