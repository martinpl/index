<?php

use App\Facades\AdminBar;
use App\Facades\DashboardMenu;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('menu items are nested, sorted and filtered for the current user', function () {
    actingAs(User::factory()->create());

    AdminBar::add('tools', ['title' => 'Tools']);
    AdminBar::add('import', [
        'title' => 'Import',
        'url' => '/import',
        'parent' => 'tools',
        'position' => 20,
    ]);
    AdminBar::add('export', [
        'title' => 'Export',
        'url' => '/export',
        'parent' => 'tools',
        'position' => 5,
    ]);
    AdminBar::add('secret', [
        'title' => 'Secret',
        'url' => '/secret',
        'parent' => 'tools',
        'can' => 'see-secrets',
    ]);
    AdminBar::add('hidden', [
        'title' => 'Hidden',
        'url' => '/hidden',
        'parent' => 'tools',
        'can' => fn (User $user) => false,
    ]);

    $tools = AdminBar::tree()->firstWhere('key', 'tools');

    expect($tools['children']->pluck('key')->all())
        ->toBe(['export', 'import']);
});

test('registering a duplicate menu item throws', function () {
    DashboardMenu::add('tools', ['title' => 'Tools']);
    DashboardMenu::add('tools', ['title' => 'Tools']);
})->throws(DomainException::class, 'Menu item [tools] is already registered.');
