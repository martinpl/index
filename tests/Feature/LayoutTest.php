<?php

use App\Entries\Layout;
use App\Entries\Section;
use App\Facades\DashboardMenu;
use App\Facades\Layouts;
use App\Models\Entry;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;

/**
 * A published page with a paragraph, shown in the layout with the given slug, or in the default one when none is given.
 */
function pageInLayout(?string $layout = null): Entry
{
    $page = Entry::factory()->published()->create(['type' => 'page', 'content' => [['type' => 'paragraph', 'data' => ['text' => 'Page text']]]]);

    if ($layout) {
        $page->setMeta('layout', $layout, 'core');
    }

    return $page;
}

describe('on the site', function () {
    beforeEach(function () {
        Setting::set('active_theme', 'howdy', 'core');
    });

    test('entries are shown in the default layout, its content block placing their content among its blocks', function () {
        Layout::factory()->default()->create(['content' => [
            ['type' => 'heading', 'data' => ['text' => 'Above']],
            ['type' => 'content', 'attributes' => ['id' => 'main']],
            ['type' => 'paragraph', 'data' => ['text' => 'Below']],
        ]]);

        get(pageInLayout()->url())
            ->assertOk()
            ->assertSeeInOrder(['<h2 class="block-heading">Above</h2>', '<main class="block-content" id="main">', 'Page text', '</main>', 'Below'], false);
    });

    test('entries are shown in the layout chosen for them, or in the default one once it is deleted', function () {
        Layout::factory()->default()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Default layout']], ['type' => 'content']]]);
        $landing = Layout::factory()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Landing layout']], ['type' => 'content']]]);
        $page = pageInLayout($landing->slug);

        get($page->url())->assertOk()->assertSeeInOrder(['Landing layout', 'Page text'])->assertDontSee('Default layout');

        $landing->forceDelete();

        get($page->url())->assertOk()->assertSeeInOrder(['Default layout', 'Page text'])->assertDontSee('Landing layout');
    });

    test('entries are shown in a layout added by a package as it was added, until it is saved with edits', function () {
        Layouts::register('landing', ['content' => [['type' => 'paragraph', 'data' => ['text' => 'Added layout']], ['type' => 'content']]], 'plugins/landing');
        $page = pageInLayout('landing');

        get($page->url())->assertOk()->assertSeeInOrder(['Added layout', 'Page text']);

        Layout::factory()->create(['slug' => 'landing', 'content' => [['type' => 'paragraph', 'data' => ['text' => 'Edited layout']], ['type' => 'content']]]);

        get($page->url())->assertOk()->assertSeeInOrder(['Edited layout', 'Page text'])->assertDontSee('Added layout');
    });

    test('layouts show the content after their blocks without a content block, and only in the first of several', function (array $content, array $order) {
        Layout::factory()->default()->create(['content' => $content]);

        $html = get(pageInLayout()->url())->assertOk()->assertSeeInOrder($order)->getContent();

        expect(substr_count($html, 'Page text'))->toBe(1);
    })->with([
        'no content block' => [[['type' => 'paragraph', 'data' => ['text' => 'Layout text']]], ['Layout text', 'Page text']],
        'two content blocks' => [[['type' => 'content'], ['type' => 'paragraph', 'data' => ['text' => 'Layout text']], ['type' => 'content']], ['Page text', 'Layout text']],
    ]);

    test('content blocks among the content of an entry render nothing', function () {
        Layout::factory()->default()->create(['content' => [['type' => 'content']]]);
        $page = Entry::factory()->published()->create(['type' => 'page', 'content' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Page text']],
            ['type' => 'content'],
        ]]);

        $html = get($page->url())->assertOk()->getContent();

        expect(substr_count($html, 'Page text'))->toBe(1)
            ->and(substr_count($html, '<main'))->toBe(1);
    });

    test('layouts are internal entries, not served as pages', function () {
        Layout::factory()->default()->create();

        expect(Entry::count())->toBe(0);
        get('/layouts/default')->assertNotFound();
    });
});

test('entries are rendered in their layout, or alone without a default one, while internal entries have none at all', function () {
    Layouts::register('landing', ['content' => [['type' => 'heading', 'data' => ['text' => 'Landing']], ['type' => 'content']]], 'plugins/landing');

    expect(pageInLayout()->layout())->toBeNull()
        ->and(pageInLayout()->render()->toHtml())->toBe('<p class="block-paragraph">Page text</p>')
        ->and(pageInLayout('landing')->render()->toHtml())->toBe(implode("\n", ['<h2 class="block-heading">Landing</h2>', '<main class="block-content"><p class="block-paragraph">Page text</p></main>']))
        ->and(Section::factory()->create()->layout())->toBeNull();
});

test('the layouts of the site are the ones added by packages, unless saved with edits, followed by the ones of users', function () {
    Layouts::register('default', ['name' => 'Main', 'content' => [['type' => 'heading'], ['type' => 'content']]], 'themes/acme');
    Layouts::register('blank', ['content' => [['type' => 'content']]], 'themes/acme');
    Layout::factory()->create(['slug' => 'blank', 'name' => 'My blank']);
    Layout::factory()->create(['slug' => 'landing', 'name' => 'Landing']);
    Layout::factory()->trashed()->create(['slug' => 'old']);

    $layouts = Layout::available();

    expect($layouts->map(fn (Layout $layout): array => [$layout->name, $layout->exists])->all())->toBe([
        'default' => ['Main', false],
        'blank' => ['My blank', true],
        'landing' => ['Landing', true],
    ])->and($layouts['default']->content->map->type()->all())->toBe(['heading', 'content'])
        ->and(Layout::withAnyStatus()->count())->toBe(3);
});

test('registering a duplicate layout throws', function () {
    Layouts::register('default', [], 'themes/acme');
    Layouts::register('default', [], 'plugins/landing');
})->throws(DomainException::class, 'Layout [default] is already registered by [themes/acme].');

describe('in the dashboard', function () {
    beforeEach(function () {
        actingAs(tap(User::factory()->create())->grantAbilities(['themes.manage']));
    });

    test('lists the layouts under the appearance of the site', function () {
        $appearance = DashboardMenu::tree()->firstWhere('key', 'appearance');

        expect($appearance['children']->pluck('title', 'url')->all())->toBe([
            route('dashboard.themes') => 'Themes',
            route('dashboard.entries', 'layout') => 'Layouts',
        ])->and(DashboardMenu::tree()->pluck('key'))->not->toContain('entries.layout');
    });

    test('lists the layouts added by packages and by users, marking the default and the edited ones, saving none', function () {
        Layouts::register('default', ['name' => 'Main', 'content' => [['type' => 'content']]], 'themes/acme');
        Layouts::register('blank', ['content' => [['type' => 'content']]], 'themes/acme');
        $blank = Layout::factory()->create(['slug' => 'blank', 'name' => 'My blank']);
        Layout::factory()->create(['name' => 'Landing']);

        get(route('dashboard.entries', 'layout'))
            ->assertOk()
            ->assertSeeInOrder([
                route('dashboard.entries.create', ['type' => 'layout', 'from' => 'default']), 'Main', 'Default',
                route('dashboard.entries.edit', ['layout', $blank]), 'My blank', 'Edited',
                'Landing',
            ]);

        expect(Layout::withAnyStatus()->count())->toBe(2);
    });

    test('users who cannot manage themes cannot list the layouts', function () {
        actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

        get(route('dashboard.entries', 'layout'))->assertForbidden();
    });

    test('resets an edited layout added by a package, showing it as it was added again', function () {
        Layouts::register('default', ['name' => 'Main', 'content' => [['type' => 'heading'], ['type' => 'content']]], 'themes/acme');
        $edited = Layout::factory()->default()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Edited']]]]);
        $landing = Layout::factory()->create();
        $listing = Livewire::test('pages::dashboard.entries', ['type' => 'layout']);

        $listing->call('runAction', 'reset', $landing->id)->assertNotDispatched('toast');
        $listing->call('runAction', 'reset', $edited->id)->assertDispatched('toast', title: '1 layout reset.');

        assertModelMissing($edited);
        assertModelExists($landing);
        expect(Layout::default()->content->map->type()->all())->toBe(['heading', 'content']);
    });

    test('trashes the layouts added by users, but neither the layouts added by packages nor their edits, which are reset instead', function () {
        Layouts::register('default', ['content' => [['type' => 'content']]], 'themes/acme');
        Layouts::register('blank', ['content' => [['type' => 'content']]], 'themes/acme');
        $blank = Layout::factory()->create(['slug' => 'blank']);
        $landing = Layout::factory()->create(['name' => 'Landing']);
        $listing = Livewire::test('pages::dashboard.entries', ['type' => 'layout']);

        $listing->call('runAction', 'trash', 'added:default')->assertNotDispatched('toast');
        $listing->call('runAction', 'trash', $blank->id)->assertNotDispatched('toast');
        $listing->call('runAction', 'trash', $landing->id)->assertDispatched('toast', title: '1 entry moved to the trash.');

        expect($landing->fresh()->trashed())->toBeTrue()
            ->and($blank->fresh()->trashed())->toBeFalse();
        $listing->set('tab', 'trash')->assertSee('Landing');
    });
});
