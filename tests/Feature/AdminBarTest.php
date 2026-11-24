<?php

use App\Entries\Layout;
use App\Facades\AdminBar;
use App\Models\Entry;
use App\Models\Setting;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('the new menu links to adding an entry of each type the user may manage', function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

    $new = AdminBar::tree()->firstWhere('key', 'new');

    expect($new['children']->pluck('url', 'title')->all())->toBe([
        'Page' => route('dashboard.entries.create', 'page'),
        'Section' => route('dashboard.entries.create', 'section'),
    ]);
});

test('the new menu lists the entry types in the order of the dashboard menu, then users', function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage', 'entries.media.manage', 'themes.manage', 'users.manage']));

    $new = AdminBar::tree()->firstWhere('key', 'new');

    expect($new['children']->pluck('title')->all())->toBe(['Page', 'Media', 'Section', 'Layout', 'User']);
});

test('the new menu links to adding a user for users who may manage them', function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['users.manage']));

    $new = AdminBar::tree()->firstWhere('key', 'new');

    expect($new['children']->pluck('url', 'title')->all())->toBe(['User' => route('dashboard.users.create')]);
});

test('the new menu is hidden from users who may manage neither entries nor users', function () {
    actingAs(User::factory()->create());

    expect(AdminBar::tree()->pluck('key'))->not->toContain('new');
});

test('a menu links to its first item', function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

    $html = get(route('dashboard.home'))->assertOk()->getContent();

    expect($html)->toMatch('~<a\s(?=[^>]*data-slot="menubar-trigger")[^>]*href="'.preg_quote(route('dashboard.entries.create', 'page'), '~').'"~');
});

describe('on the site', function () {
    beforeEach(function () {
        Setting::set('active_theme', 'howdy', 'core');
    });

    test('links to editing the shown entry and its layout for users who may manage them', function () {
        $layout = Layout::factory()->default()->create();
        $page = Entry::factory()->published()->create(['type' => 'page']);
        actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage', 'themes.manage']));

        get($page->url())
            ->assertOk()
            ->assertSeeInOrder(['href="'.$page->editUrl().'"', 'Edit Page', 'href="'.$layout->editUrl().'"', 'Edit Layout'], false);
    });

    test('links to editing the layout only for users who may manage the themes', function () {
        $layout = Layout::factory()->default()->create();
        $page = Entry::factory()->published()->create(['type' => 'page']);
        actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

        get($page->url())
            ->assertOk()
            ->assertSee('href="'.$page->editUrl().'"', false)
            ->assertDontSee('href="'.$layout->editUrl().'"', false);
    });

    test('links to editing the shown entry only for users who may manage its type', function () {
        $page = Entry::factory()->published()->create(['type' => 'page']);
        actingAs(User::factory()->create());

        get($page->url())
            ->assertOk()
            ->assertDontSee('href="'.$page->editUrl().'"', false);
    });

    test('links to the themes, the plugins and the settings for users who may manage them', function () {
        $page = Entry::factory()->published()->create(['type' => 'page']);
        actingAs(tap(User::factory()->create())->grantAbilities(['themes.manage', 'plugins.manage', 'settings.manage']));

        get($page->url())
            ->assertOk()
            ->assertSee('href="'.route('dashboard.plugins').'"', false)
            ->assertSee('href="'.route('dashboard.themes').'"', false)
            ->assertSee('href="'.route('dashboard.settings', 'general').'"', false);
    });

    test('links to the themes, the plugins and the settings only for users who may manage them', function () {
        $page = Entry::factory()->published()->create(['type' => 'page']);
        actingAs(User::factory()->create());

        get($page->url())
            ->assertOk()
            ->assertDontSee('href="'.route('dashboard.themes').'"', false)
            ->assertDontSee('href="'.route('dashboard.plugins').'"', false)
            ->assertDontSee('href="'.route('dashboard.settings', 'general').'"', false);
    });
});
