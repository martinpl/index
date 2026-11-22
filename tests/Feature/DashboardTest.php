<?php

use App\Facades\DashboardMenu;
use App\Facades\EntryType;
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
