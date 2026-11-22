<?php

use App\Entries\Layout;
use App\Entries\Section;
use App\Enums\EntryStatus;
use App\Facades\Layouts;
use App\Models\Entry;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage', 'themes.manage']));
});

function pageEditor(?Entry $page = null): Testable
{
    return Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $page?->id]);
}

function layoutEditor(?Layout $layout = null): Testable
{
    return Livewire::test('pages::dashboard.entry', ['type' => 'layout', 'entry' => $layout?->id]);
}

test('entries are edited in the default layout until another is chosen, storing only a layout other than the default one', function () {
    $default = Layout::factory()->default()->create();
    $landing = Layout::factory()->create(['name' => 'Landing']);
    $page = Entry::factory()->create(['type' => 'page']);

    $editor = pageEditor($page)->assertSet('metaboxes.layout.layout', 'default')->assertSee('Landing');

    $editor->set('metaboxes.layout.layout', $landing->slug)->call('save')->assertHasNoErrors();

    expect($page->fresh()->getMeta('layout'))->toBe($landing->slug);
    pageEditor($page)->assertSet('metaboxes.layout.layout', $landing->slug);

    $editor->set('metaboxes.layout.layout', 'default')->call('save')->assertHasNoErrors();

    expect($page->fresh()->hasMeta('layout'))->toBeFalse();
});

test('adds an entry in the chosen layout', function () {
    Layout::factory()->default()->create();
    $landing = Layout::factory()->create();

    pageEditor()->set('name', 'About')->set('metaboxes.layout.layout', $landing->slug)->call('save')->assertHasNoErrors();

    expect(Entry::withAnyStatus()->where('type', 'page')->sole()->getMeta('layout'))->toBe($landing->slug);
});

test('rejects a layout that is not among the layouts of the site', function () {
    Layout::factory()->default()->create();
    $trashed = Layout::factory()->trashed()->create();
    $page = Entry::factory()->create(['type' => 'page']);

    pageEditor($page)
        ->set('metaboxes.layout.layout', $trashed->slug)
        ->call('save')
        ->assertHasErrors(['metaboxes.layout.layout' => 'The selected layout is invalid.']);

    expect($page->fresh()->hasMeta('layout'))->toBeFalse();
});

test('offers the layouts added by packages before they are saved with edits, linking to editing them, saving none', function () {
    Layouts::register('default', ['name' => 'Main', 'content' => [['type' => 'content']]], 'themes/acme');

    pageEditor()
        ->assertSet('metaboxes.layout.layout', 'default')
        ->assertSee(['Main', route('dashboard.entries.create', ['type' => 'layout', 'from' => 'default'])]);

    expect(Layout::withAnyStatus()->count())->toBe(0);
});

test('edits a layout added by a package as a new layout of its slug, starting from the blocks it was added with', function () {
    Layouts::register('default', ['name' => 'Main', 'content' => [['type' => 'heading', 'data' => ['text' => 'Hello']], ['type' => 'content']]], 'themes/acme');

    $editor = Livewire::withQueryParams(['from' => 'default'])
        ->test('pages::dashboard.entry', ['type' => 'layout'])
        ->assertSet('name', 'Main')
        ->assertSet('values.slug', 'default')
        ->assertSee('Hello');

    $editor->set('blocks.0.data.text', 'Welcome')->call('save')->assertHasNoErrors();

    $layout = Layout::sole();
    expect($layout->only(['slug', 'name']))->toBe(['slug' => 'default', 'name' => 'Main'])
        ->and($layout->content->pluck('data.text')->all())->toBe(['Welcome', null])
        ->and(Layout::default()->is($layout))->toBeTrue();
});

test('internal entries have no layout in the editor', function () {
    Layout::factory()->default()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Default layout']], ['type' => 'content']]]);

    $editor = Livewire::test('pages::dashboard.entry', ['type' => 'section', 'entry' => Section::factory()->create()->id]);

    expect($editor->get('metaboxes'))->not->toHaveKey('layout');
    $editor->assertDontSee('Default layout');
});

test('previews the blocks being edited in the chosen layout, which cannot be interacted with', function () {
    Layout::factory()->default()->create(['content' => [
        ['id' => 'above', 'type' => 'paragraph', 'data' => ['text' => 'Above the content']],
        ['type' => 'content', 'attributes' => ['class' => 'wide']],
        ['id' => 'below', 'type' => 'paragraph', 'data' => ['text' => 'Below the content']],
    ]]);
    $blank = Layout::factory()->create(['content' => [['type' => 'content']]]);
    $page = Entry::factory()->create(['type' => 'page', 'content' => [['id' => 'text', 'type' => 'paragraph', 'data' => ['text' => 'Page text']]]]);

    $editor = pageEditor($page)->assertSeeHtmlInOrder([
        'wire:key="layout-above" class="opacity-60" inert',
        'Above the content',
        '<div class="block-content wide">',
        'wire:key="preview-text"',
        'Page text',
        'wire:key="layout-below" class="opacity-60" inert',
        'Below the content',
    ]);

    $editor->set('metaboxes.layout.layout', $blank->slug)->assertSee('Page text')->assertDontSee(['Above the content', 'Below the content']);
});

test('previews where layouts show the content of their entries', function () {
    layoutEditor(Layout::factory()->create(['content' => [['type' => 'content']]]))
        ->assertSee('The content of each entry shown in the layout goes here.');
});

test('the content block is only offered in the editor of layouts', function () {
    pageEditor()->assertDontSee('Where the layout shows the content of its entries.')->call('addBlock', 'content')->assertNotFound();

    layoutEditor()->assertSee('Where the layout shows the content of its entries.')->call('addBlock', 'content')->assertSet('blocks.0.type', 'content');
});

test('adds a layout of blocks as an internal entry', function () {
    layoutEditor()
        ->set('name', 'Landing')
        ->call('addBlock', 'heading')
        ->call('addBlock', 'content')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Layout added.');

    $layout = Layout::sole();

    expect($layout->only(['status', 'name', 'slug', 'owner']))->toBe([
        'status' => EntryStatus::Internal->value,
        'name' => 'Landing',
        'slug' => 'landing',
        'owner' => 'core',
    ])->and($layout->content->map->type()->all())->toBe(['heading', 'content']);
});

test('links to editing the chosen layout only for users who can manage themes', function () {
    $default = Layout::factory()->default()->create();
    $page = Entry::factory()->create(['type' => 'page']);

    pageEditor($page)->assertSee(route('dashboard.entries.edit', ['layout', $default]));

    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));

    pageEditor($page)->assertSet('metaboxes.layout.layout', 'default')->assertDontSee(route('dashboard.entries.edit', ['layout', $default]));
    get(route('dashboard.entries.edit', ['layout', $default]))->assertForbidden();
});
