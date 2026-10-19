<?php

namespace App\Hooks;

use App\Attributes\Action;
use App\Facades\EntryType;
use App\Facades\TermType;
use App\Foundation\AdminBar;
use App\Foundation\DashboardMenu;
use Illuminate\Support\Str;

class Menus
{
    #[Action('admin-bar.menu')]
    public function adminBar(AdminBar $bar): void
    {
        $inDashboard = fn (): bool => request()->routeIs('dashboard.*', 'profile.*', 'security.*');

        $bar->add('site', ['title' => option('site_name'), 'icon' => 'app-logo-icon', 'position' => 0]);
        $bar->add('dashboard', ['title' => 'Dashboard', 'url' => route('dashboard.home'), 'icon' => 'lucide-layout-dashboard', 'parent' => 'site', 'position' => 0, 'can' => fn (): bool => ! $inDashboard()]);
        $bar->add('home', ['title' => 'Home', 'url' => fn (): ?string => page_url('home'), 'icon' => 'lucide-home', 'parent' => 'site', 'position' => 0, 'can' => $inDashboard]);
    }

    #[Action('dashboard.menu')]
    public function dashboard(DashboardMenu $menu): void
    {
        $menu->add('dashboard', ['title' => 'Dashboard', 'url' => route('dashboard.home'), 'icon' => 'lucide-layout-dashboard', 'position' => 0]);

        foreach (EntryType::list() as $entryType) {
            $menu->add("entries.{$entryType['key']}", [
                'title' => Str::plural($entryType['name']),
                'url' => route('dashboard.entries', $entryType['key']),
                'icon' => $entryType['icon'],
            ]);

            foreach (TermType::list()->filter(fn (array $termType): bool => in_array($entryType['key'], $termType['entry_types'])) as $termType) {
                $menu->add("entries.{$entryType['key']}.{$termType['key']}", [
                    'title' => Str::plural($termType['name']),
                    'url' => route('dashboard.terms', $termType['key']),
                    'parent' => "entries.{$entryType['key']}",
                ]);
            }
        }
    }
}
