<?php

use App\Entries\Section;
use App\Enums\EntryStatus;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
});

function sectionEditor(?Section $section = null): Testable
{
    return Livewire::test('pages::dashboard.entry', ['type' => 'section', 'entry' => $section?->id]);
}

test('users without the ability cannot open the section editor', function () {
    $section = Section::factory()->create();

    actingAs(User::factory()->create());

    get(route('dashboard.entries.create', 'section'))->assertForbidden();
    get(route('dashboard.entries.edit', ['section', $section]))->assertForbidden();
});

test('sections are edited in the entry editor without a status, as they are never published', function () {
    $section = Section::factory()->create();

    get(route('dashboard.entries.edit', ['section', $section]))
        ->assertOk()
        ->assertSeeLivewire('pages::dashboard.entry')
        ->assertSee('Edit Section')
        ->assertDontSee('Published');
    sectionEditor($section)->assertSet('values', ['slug' => $section->slug]);
});

test('adds a section with blocks as an internal entry and goes on to edit it', function () {
    $editor = sectionEditor()
        ->set('name', 'Call to action')
        ->call('addBlock', 'heading')
        ->set('blocks.0.data.text', 'Join us')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Section added.');

    $section = Section::sole();

    $editor->assertRedirect(route('dashboard.entries.edit', ['section', $section]));
    expect($section->only(['type', 'status', 'name', 'slug', 'user_id', 'owner']))->toBe([
        'type' => 'section',
        'status' => EntryStatus::Internal->value,
        'name' => 'Call to action',
        'slug' => 'call-to-action',
        'user_id' => auth()->id(),
        'owner' => 'core',
    ])->and($section->content->toArray())->toBe([
        ['id' => $editor->get('blocks.0.id'), 'type' => 'heading', 'data' => ['text' => 'Join us', 'level' => 'h2'], 'attributes' => ['id' => null, 'class' => null]],
    ]);
});

test('edits the blocks of a section, letting them show other sections', function () {
    $footer = Section::factory()->create();
    $section = Section::factory()->create(['name' => 'Call to action', 'content' => [['id' => 'text', 'type' => 'paragraph', 'data' => ['text' => 'Join us']]]]);

    sectionEditor($section)
        ->assertSet('name', 'Call to action')
        ->assertSee('Join us')
        ->set('name', 'Hiring')
        ->call('removeBlock', 'text')
        ->call('addBlock', 'section')
        ->set('blocks.0.data.section', (string) $footer->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Section updated.')
        ->assertNoRedirect();

    $section->refresh();

    expect($section->name)->toBe('Hiring')
        ->and($section->content->sole()->toArray())->toMatchArray(['type' => 'section', 'data' => ['section' => (string) $footer->id]]);
});

test('validates the title and the fields of the blocks, selecting the first invalid block', function () {
    $editor = sectionEditor()
        ->call('addBlock', 'paragraph')
        ->call('addBlock', 'image');

    $editor->call('save')
        ->assertHasErrors([
            'name' => 'The title field is required.',
            'blocks.1.data.media' => 'The image field is required.',
        ])
        ->assertSet('selectedBlock', $editor->get('blocks.1.id'));

    expect(Section::withAnyStatus()->count())->toBe(0);
});

test('keeps a section from being shown inside itself', function (Closure $shown) {
    $section = Section::factory()->create(['content' => [['type' => 'paragraph', 'data' => ['text' => 'Join us']]]]);

    $editor = sectionEditor($section)
        ->call('addBlock', 'section')
        ->set('blocks.1.data.section', (string) $shown($section)->id)
        ->call('save')
        ->assertHasErrors(['blocks.1.data.section' => 'The section cannot be shown inside itself.']);

    $editor->assertSet('selectedBlock', $editor->get('blocks.1.id'));
    expect($section->fresh()->content->map->type()->all())->toBe(['paragraph']);
})->with([
    'by itself' => fn (Section $section): Section => $section,
    'by a section it shows' => fn (Section $section): Section => Section::factory()->create(['content' => [
        ['type' => 'section', 'data' => ['section' => Section::factory()->create(['content' => [
            ['type' => 'section', 'data' => ['section' => $section->id]],
        ]])->id]],
    ]]),
]);
