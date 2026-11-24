<?php

use App\Facades\DashboardMenu;
use App\Facades\EntryType;
use App\Facades\Settings;
use App\Facades\TermType;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard.home'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard.home'));
    $response->assertOk()
        ->assertSeeText($user->name)
        ->assertSeeText($user->email)
        ->assertSee(route('profile.edit'), false)
        ->assertSee('action="'.route('logout').'"', false);
});

test('the dashboard menu links to every entry type and its term types', function () {
    EntryType::register('post', [], 'core');
    TermType::register('category', ['entry_types' => ['post']], 'core');
    $this->actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

    $this->get(route('dashboard.entries', 'post'))
        ->assertOk()
        ->assertSeeInOrder(['Pages', route('dashboard.entries', 'page'), 'Posts', route('dashboard.terms', 'category'), 'Categories']);
    $this->get(route('dashboard.terms', 'category'))->assertOk();
    $this->get(route('dashboard.entries', 'missing'))->assertNotFound();
});

test('the dashboard menu links to an entry type under the item named as its menu parent', function () {
    EntryType::register('template', ['menu_parent' => 'appearance'], 'core');
    $this->actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage', 'themes.manage']));

    $menu = DashboardMenu::tree();

    expect($menu->firstWhere('key', 'appearance')['children']->pluck('url', 'key')->all())->toMatchArray(['entries.template' => route('dashboard.entries', 'template')])
        ->and($menu->pluck('key')->all())->not->toContain('entries.template');
});

test('the dashboard menu shows no sub-menu with only a link to the page of its item', function () {
    $this->actingAs(tap(User::factory()->create())->grantAbilities(['settings.manage']));

    $this->get(route('dashboard.home'))
        ->assertOk()
        ->assertSee('href="'.route('dashboard.settings', 'general').'"', false)
        ->assertDontSee('data-slot="sidebar-menu-sub-button"', false);
});

test('the dashboard menu shows the sub-menu of the current item', function () {
    Settings::register('reading', ['schema' => fn (): array => []], 'plugins/reading');
    $this->actingAs(tap(User::factory()->create())->grantAbilities(['settings.manage']));

    $this->get(route('dashboard.settings', 'reading'))
        ->assertOk()
        ->assertSeeInOrder(['data-slot="sidebar-menu-sub-button"', 'General', 'data-slot="sidebar-menu-sub-button"', 'Reading'], false);
});

test('the dashboard menu collapses the sub-menus of the other items until hovered', function () {
    Settings::register('reading', ['schema' => fn (): array => []], 'plugins/reading');
    $this->actingAs(tap(User::factory()->create())->grantAbilities(['settings.manage']));

    $this->get(route('dashboard.home'))
        ->assertOk()
        ->assertSeeInOrder(['group-hover/menu-item:visible', 'data-slot="sidebar-menu-sub"', 'href="'.route('dashboard.settings', 'reading').'"'], false);
});

test('the dashboard menu links to listing and adding the entries of a type, before its term types', function () {
    EntryType::register('post', [], 'core');
    TermType::register('category', ['entry_types' => ['post']], 'core');
    $this->actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

    $posts = DashboardMenu::tree()->firstWhere('key', 'entries.post');

    expect($posts['children']->pluck('url', 'title')->all())->toBe([
        'All Posts' => route('dashboard.entries', 'post'),
        'Add Post' => route('dashboard.entries.create', 'post'),
        'Categories' => route('dashboard.terms', 'category'),
    ]);
});

test('the dashboard menu lists the entry types by their menu position, then by key', function () {
    EntryType::register('post', ['menu_position' => 25], 'core');
    EntryType::register('book', [], 'core');
    $this->actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage', 'entries.media.manage']));

    $keys = DashboardMenu::tree()->pluck('key')->filter(fn (string $key): bool => str_starts_with($key, 'entries.'))->values()->all();

    expect($keys)->toBe(['entries.book', 'entries.page', 'entries.media', 'entries.post', 'entries.section']);
});
