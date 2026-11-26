<?php

use App\Entries\Page;
use App\Facades\PageTemplates;
use App\Foundation\Theme;
use App\Models\Entry;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;

/**
 * Add the template of the page shown when a page is not found, as a theme would, with a paragraph of the given text.
 */
function addNotFoundTemplate(string $text = 'Nothing here'): void
{
    PageTemplates::register(Page::NOT_FOUND, ['name' => 'Lost', 'content' => [['type' => 'paragraph', 'data' => ['text' => $text]]]], 'themes/acme');
}

/**
 * A published page with a paragraph of the given text, assigned to the role shown when a page is not found.
 */
function notFoundPage(string $text = 'My not found page'): Page
{
    $page = Page::find(Entry::factory()->published()->create(['type' => 'page', 'slug' => 'oops', 'content' => [['type' => 'paragraph', 'data' => ['text' => $text]]]])->id);
    $page->assignRole(Page::NOT_FOUND);

    return $page;
}

describe('on the site', function () {
    beforeEach(function () {
        Setting::set('active_theme', 'howdy', 'core');
    });

    test('paths showing no entry show the template of the not found page with a not found status, saving none', function () {
        get('/missing')
            ->assertNotFound()
            ->assertViewIs('index')
            ->assertSee(['<title>Page not found', 'The page you are looking for does not exist or was moved.'], false);

        expect(Page::withAnyStatus()->count())->toBe(0);
    });

    test('the admin bar of the not found page links to editing it, starting a page from its template', function () {
        actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

        get('/missing')->assertNotFound()->assertSee(route('dashboard.entries.create', ['type' => 'page', 'from' => Page::NOT_FOUND]), false);
    });

    test('the published page assigned to the not found role replaces its template, with a not found status at its own path too', function () {
        $page = notFoundPage();

        get('/missing')->assertNotFound()->assertSee('My not found page')->assertDontSee('The page you are looking for');
        get($page->url())->assertNotFound()->assertSee('My not found page');
    });

    test('the template is shown again once the page assigned to the not found role is trashed', function () {
        notFoundPage()->delete();

        get('/missing')->assertNotFound()->assertSee('The page you are looking for')->assertDontSee('My not found page');
    });

    test('paths showing no entry get the default not found response when the not found page has no template', function () {
        app(Theme::class)->skip();

        get('/missing')->assertNotFound()->assertDontSee('data-canvas', false);
    });
});

describe('in the dashboard', function () {
    beforeEach(function () {
        actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
    });

    test('the templates tab lists the templates of the pages of roles, or the pages assigned to them, saving none', function () {
        Page::registerRole('shop', 'Shop page');
        addNotFoundTemplate();
        PageTemplates::register('shop', ['name' => 'Shop'], 'plugins/shop');
        $shop = Page::find(Entry::factory()->published()->create(['type' => 'page', 'name' => 'My shop'])->id);
        $shop->assignRole('shop');
        Entry::factory()->published()->create(['type' => 'page', 'name' => 'About']);

        get(route('dashboard.entries', ['type' => 'page', 'tab' => 'templates']))
            ->assertOk()
            ->assertSeeInOrder([
                route('dashboard.entries.create', ['type' => 'page', 'from' => Page::NOT_FOUND]), 'Lost', 'Page not found',
                route('dashboard.entries.edit', ['page', $shop]), 'My shop', 'Shop page', 'Edited',
            ])
            ->assertDontSee(['About', 'Draft', 'wire:sort:handle'], false);

        expect(Page::withAnyStatus()->count())->toBe(2);
    });

    test('resets a page replacing a template, deleting it and unassigning its role, so the template is shown again', function () {
        addNotFoundTemplate();
        $page = notFoundPage();
        $about = Entry::factory()->published()->create(['type' => 'page']);
        $listing = Livewire::test('pages::dashboard.entries', ['type' => 'page']);

        $listing->call('runAction', 'reset', $about->id)->assertNotDispatched('toast');
        $listing->call('runAction', 'reset', $page->id)->assertDispatched('toast', title: '1 page reset.');

        assertModelMissing($page);
        assertModelExists($about);
        expect(Setting::get('page_roles'))->toBeNull()
            ->and(Page::shownFor(Page::NOT_FOUND))->exists->toBeFalse()->name->toBe('Lost');
    });

    test('pages replacing a template are reset rather than moved to the trash', function () {
        addNotFoundTemplate();
        $page = notFoundPage();
        $about = Entry::factory()->published()->create(['type' => 'page']);
        $listing = Livewire::test('pages::dashboard.entries', ['type' => 'page']);

        $listing->call('runAction', 'trash', $page->id)->assertNotDispatched('toast');
        $listing->call('runAction', 'trash', $about->id)->assertDispatched('toast', title: '1 entry moved to the trash.');

        expect($page->fresh()->status)->toBe('publish')
            ->and($about->fresh()->status)->toBe('trash');
    });

    test('the pages offer no templates tab without templates', function () {
        Livewire::test('pages::dashboard.entries', ['type' => 'page'])->assertDontSee('Templates');
    });

    test('dragging the pages of the templates tab keeps their order', function () {
        addNotFoundTemplate();
        PageTemplates::register('home', ['name' => 'Welcome'], 'themes/acme');
        $page = notFoundPage();
        $other = Entry::factory()->published()->create(['type' => 'page', 'order' => 1]);

        Livewire::test('pages::dashboard.entries', ['type' => 'page'])
            ->set('tab', 'templates')
            ->call('reorder', $page->id, 1);

        expect($page->fresh()->order)->toBe(0)
            ->and($other->fresh()->order)->toBe(1);
    });

    test('edits a template as a new published page assigned to its role, starting from its blocks', function () {
        addNotFoundTemplate();

        $editor = Livewire::withQueryParams(['from' => Page::NOT_FOUND])
            ->test('pages::dashboard.entry', ['type' => 'page'])
            ->assertSet('name', 'Lost')
            ->assertSet('values.slug', 'lost')
            ->assertSet('values.status', 'publish')
            ->assertSet('metaboxes.page-role.role', Page::NOT_FOUND)
            ->assertSee('Nothing here');

        $editor->set('blocks.0.data.text', 'Edited')->call('save')->assertHasNoErrors();

        $page = Page::sole();
        expect($page->content->pluck('data.text')->all())->toBe(['Edited'])
            ->and(Page::for(Page::NOT_FOUND)->is($page))->toBeTrue();
    });

    test('adds a page with no role when it is started from a role without a template', function () {
        Livewire::withQueryParams(['from' => 'missing'])
            ->test('pages::dashboard.entry', ['type' => 'page'])
            ->assertSet('name', '')
            ->assertSet('metaboxes.page-role.role', null);
    });
});

test('a template cannot be added for a role that already has one', function () {
    addNotFoundTemplate();
    addNotFoundTemplate();
})->throws(DomainException::class, 'Page template [not_found] is already registered by [themes/acme].');
