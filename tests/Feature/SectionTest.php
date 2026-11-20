<?php

use App\Entries\Page;
use App\Entries\Section;
use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('active_theme', 'howdy', 'core');
});

/**
 * A published page showing the given section in a section block, with the given HTML attributes.
 *
 * @param  array<string, string>  $attributes
 */
function pageShowing(Section $section, array $attributes = []): Entry
{
    return Entry::factory()->published()->create(['type' => 'page', 'content' => [
        ['id' => 'shared', 'type' => 'section', 'data' => ['section' => $section->id], 'attributes' => $attributes],
    ]]);
}

test('sections are internal entries, left out of the published entries and not served as pages', function () {
    $section = Section::factory()->create(['slug' => 'call-to-action']);

    expect(Entry::count())->toBe(0)
        ->and(Section::sole()->is($section))->toBeTrue();
    get('/sections/call-to-action')->assertNotFound();
});

test('section blocks render the current blocks of their section, wherever it is shown', function () {
    $section = Section::factory()->create(['content' => [['type' => 'heading', 'data' => ['text' => 'Join us']]]]);
    $about = pageShowing($section, ['id' => 'cta']);
    $contact = pageShowing($section);

    get($about->url())
        ->assertOk()
        ->assertSee('<div class="block-section" id="cta">', false)
        ->assertSee('<h2 class="block-heading">Join us</h2>', false);

    $section->update(['content' => [['type' => 'paragraph', 'data' => ['text' => 'We are hiring']]]]);

    get($about->url())->assertOk()->assertSee('We are hiring')->assertDontSee('Join us');
    get($contact->url())->assertOk()->assertSee('We are hiring');
});

test('section blocks render nothing once their section is deleted', function () {
    $section = Section::factory()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Join us']]]]);
    $page = pageShowing($section);

    $section->forceDelete();

    get($page->url())->assertOk()->assertDontSee(['block-section', 'Join us']);
});

test('trashing a section hides it wherever it is shown until it is restored', function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
    $section = Section::factory()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Join us']]]]);
    $page = pageShowing($section);
    $sectionsPage = Livewire::test('pages::dashboard.entries', ['type' => 'section']);

    $sectionsPage->call('runAction', 'trash', $section->id)->assertDispatched('toast', title: '1 entry moved to the trash.');

    get($page->url())->assertOk()->assertDontSee('Join us');

    $sectionsPage->set('tab', 'trash')->call('runAction', 'restore', $section->id);

    expect($section->refresh()->status)->toBe(EntryStatus::Internal->value);
    get($page->url())->assertOk()->assertSee('Join us');
});

test('sections shown by their own blocks, directly or through other sections, render once', function () {
    $header = Section::factory()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Header text']]]]);
    $menu = Section::factory()->create(['content' => [
        ['type' => 'paragraph', 'data' => ['text' => 'Menu text']],
        ['type' => 'section', 'data' => ['section' => $header->id]],
    ]]);
    $header->update(['content' => [
        ['type' => 'paragraph', 'data' => ['text' => 'Header text']],
        ['type' => 'section', 'data' => ['section' => $header->id]],
        ['type' => 'section', 'data' => ['section' => $menu->id]],
    ]]);

    $html = get(pageShowing($header)->url())->assertOk()->getContent();

    expect(substr_count($html, 'Header text'))->toBe(1)
        ->and(substr_count($html, 'Menu text'))->toBe(1);
});

describe('in the entry editor', function () {
    beforeEach(function () {
        actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
    });

    test('shows the chosen section in section blocks, linking to editing it, or to adding one when there are none', function () {
        $editor = Livewire::test('pages::dashboard.entry', ['type' => 'page'])
            ->call('addBlock', 'section')
            ->assertSee(route('dashboard.entries.create', 'section'))
            ->assertDontSee('Detach');
        $section = Section::factory()->create(['name' => 'Call to action', 'content' => [['type' => 'paragraph', 'data' => ['text' => 'Join us']]]]);

        $editor->set('blocks.0.data.section', (string) $section->id)
            ->assertSee('Call to action')
            ->assertSee(['Join us', route('dashboard.entries.edit', ['section', $section]), 'Detach'])
            ->assertDontSee(route('dashboard.entries.create', 'section'));
    });

    test('section blocks only accept sections that are not trashed', function (?Closure $section, string $error) {
        Livewire::test('pages::dashboard.entry', ['type' => 'page'])
            ->set('name', 'About')
            ->call('addBlock', 'section')
            ->set('blocks.0.data.section', $section ? (string) $section()->id : null)
            ->call('save')
            ->assertHasErrors(['blocks.0.data.section' => $error]);

        expect(Page::withAnyStatus()->count())->toBe(0);
    })->with([
        'no section' => [null, 'The section field is required.'],
        'a trashed section' => [fn (): Section => Section::factory()->trashed()->create(), 'The selected section is invalid.'],
    ]);

    test('detaches a section block into copies of the blocks of its section, selecting the first', function () {
        $section = Section::factory()->create(['content' => [
            ['id' => 'title', 'type' => 'heading', 'data' => ['text' => 'Join us']],
            ['id' => 'text', 'type' => 'paragraph', 'data' => ['text' => 'We are hiring']],
        ]]);
        $page = Entry::factory()->create(['type' => 'page', 'content' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Before']],
            ['id' => 'shared', 'type' => 'section', 'data' => ['section' => $section->id]],
            ['type' => 'paragraph', 'data' => ['text' => 'After']],
        ]]);

        $editor = Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $page->id])
            ->call('detachBlock', 'shared')
            ->call('save')
            ->assertHasNoErrors();

        $content = $page->fresh()->content;

        expect($content->pluck('data.text')->all())->toBe(['Before', 'Join us', 'We are hiring', 'After'])
            ->and($content->pluck('id')->intersect(['title', 'text', 'shared']))->toBeEmpty();
        $editor->assertSet('selectedBlock', $content[1]->id);
        expect($section->fresh()->content->pluck('id')->all())->toBe(['title', 'text']);
    });

    test('cannot detach blocks other than section blocks showing a section', function (string $block) {
        $section = Section::factory()->create();
        $page = Entry::factory()->create(['type' => 'page', 'content' => [
            ['id' => 'text', 'type' => 'paragraph', 'data' => ['text' => 'Hello']],
            ['id' => 'shared', 'type' => 'section', 'data' => ['section' => $section->id]],
        ]]);
        $section->forceDelete();

        Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $page->id])->call('detachBlock', $block)->assertNotFound();
    })->with([
        'a paragraph block' => 'text',
        'a section block of a deleted section' => 'shared',
    ]);
});
