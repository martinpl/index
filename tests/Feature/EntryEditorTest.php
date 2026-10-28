<?php

use App\Blocks\Block;
use App\Blocks\Heading;
use App\Blocks\Missing;
use App\Blocks\Paragraph;
use App\Enums\EntryStatus;
use App\Facades\EntryType;
use App\Models\Entry;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
});

function entryEditor(?Entry $entry = null): Testable
{
    return Livewire::test('pages::dashboard.entry', ['type' => $entry->type ?? 'page', 'entry' => $entry?->id]);
}

test('users without the ability of the entry type cannot open the editor', function () {
    EntryType::register('product', ['can' => 'entries.product.manage'], 'core');
    $entry = Entry::factory()->create(['type' => 'product']);

    get(route('dashboard.entries.create', 'product'))->assertForbidden();
    get(route('dashboard.entries.edit', ['product', $entry]))->assertForbidden();
});

test('unknown entry types and entries of another type are not found', function () {
    $post = Entry::factory()->create(['type' => 'post']);

    get(route('dashboard.entries.create', 'missing'))->assertNotFound();
    get(route('dashboard.entries.edit', ['page', $post]))->assertNotFound();
});

test('adds an entry with blocks and goes on to edit it', function () {
    $user = auth()->user();

    $editor = entryEditor()
        ->set('name', 'About us')
        ->call('addBlock', 'paragraph')
        ->call('addBlock', 'heading', 0);

    $editor->set('blocks.0.data.text', 'Who we are')
        ->set('blocks.1.data.text', 'We build Index.')
        ->call('save')
        ->assertHasNoErrors();

    $entry = Entry::withAnyStatus()->where('type', 'page')->sole();

    $editor->assertRedirect(route('dashboard.entries.edit', ['page', $entry]));

    expect($entry->only(['name', 'slug', 'status', 'user_id', 'owner']))->toBe([
        'name' => 'About us',
        'slug' => 'about-us',
        'status' => EntryStatus::Draft->value,
        'user_id' => $user->id,
        'owner' => 'core',
    ])->and($entry->content->map(fn (Block $block): array => [$block->type(), $block->data])->all())->toBe([
        ['heading', ['text' => 'Who we are', 'level' => 'h2']],
        ['paragraph', ['text' => 'We build Index.']],
    ]);
});

test('edits the blocks of a draft entry', function () {
    $entry = Entry::factory()->create(['type' => 'page', 'content' => [
        new Heading(['text' => 'Title'], 'heading'),
        new Paragraph(['text' => 'First'], 'first'),
        new Paragraph(['text' => 'Second'], 'second'),
    ]]);

    get(route('dashboard.entries.edit', ['page', $entry]))
        ->assertOk()
        ->assertSeeInOrder(['Structure', 'Heading', 'Paragraph', '<h2 >Title</h2>', '<p >First</p>'], false)
        ->assertSee("addBlock('image', null)", false)
        ->assertDontSee('@js(', false);

    $editor = entryEditor($entry)
        ->assertSet('name', $entry->name)
        ->assertSee('Title')
        ->call('moveBlock', 'second', 0)
        ->call('duplicateBlock', 'first')
        ->call('removeBlock', 'heading')
        ->set('values.status', EntryStatus::Publish->value)
        ->call('save')
        ->assertHasNoErrors();

    $content = $entry->fresh()->content;

    expect($content->map->type()->all())->toBe(['paragraph', 'paragraph', 'paragraph'])
        ->and($content->pluck('data.text')->all())->toBe(['Second', 'First', 'First'])
        ->and($content->pluck('id')->unique())->toHaveCount(3)
        ->and($entry->fresh()->status)->toBe(EntryStatus::Publish->value);

    $editor->assertSet('blocks', $content->map->toArray()->all());
});

test('keeps blocks of unregistered types when saving', function () {
    $entry = Entry::factory()->create(['type' => 'page', 'content' => [['id' => 'gallery', 'type' => 'gallery', 'data' => ['images' => [1]]]]]);

    entryEditor($entry)
        ->assertSee('The gallery block type is not available')
        ->call('save')
        ->assertHasNoErrors();

    expect($entry->fresh()->content->first())->toBeInstanceOf(Missing::class)
        ->and($entry->fresh()->content->first()->toArray())->toBe(['id' => 'gallery', 'type' => 'gallery', 'data' => ['images' => [1]], 'attributes' => ['id' => null, 'class' => null]]);
});

test('validates the title and the fields of the blocks, selecting the first invalid block', function () {
    $editor = entryEditor()
        ->call('addBlock', 'paragraph')
        ->call('addBlock', 'image')
        ->call('addBlock', 'heading');

    $editor->set('values.slug', 'Not a slug')
        ->set('blocks.0.attributes.id', '1st paragraph')
        ->call('save')
        ->assertHasErrors([
            'name' => 'The title field is required.',
            'values.slug',
            'blocks.0.attributes.id' => 'The html id field format is invalid.',
            'blocks.1.data.url' => 'The image url field is required.',
        ])
        ->assertHasNoErrors(['blocks.0.data.text', 'blocks.2.data.text'])
        ->assertSet('selectedBlock', $editor->get('blocks.0.id'));

    expect(Entry::withAnyStatus()->count())->toBe(0);
});

test('stays on the selected block when it is invalid, and leaves the blocks when only the entry is invalid', function () {
    $editor = entryEditor()
        ->set('name', 'About')
        ->call('addBlock', 'image')
        ->call('addBlock', 'image');

    $editor->call('save')->assertSet('selectedBlock', $editor->get('blocks.1.id'));

    $editor->set('blocks.0.data.url', 'https://example.com/a.jpg')
        ->set('blocks.1.data.url', 'https://example.com/b.jpg')
        ->set('values.slug', 'Not a slug')
        ->call('save')
        ->assertHasErrors('values.slug')
        ->assertSet('selectedBlock', null);
});

test('selects added and duplicated blocks, and leaves removed ones', function () {
    $editor = entryEditor()->call('addBlock', 'paragraph');
    $first = $editor->get('blocks.0.id');

    $editor->assertSet('selectedBlock', $first)
        ->call('duplicateBlock', $first)
        ->assertSet('selectedBlock', $editor->get('blocks.1.id'))
        ->call('selectBlock', $first)
        ->assertSet('selectedBlock', $first)
        ->assertSee('Advanced')
        ->call('removeBlock', $first)
        ->assertSet('selectedBlock', null)
        ->call('selectBlock', 'missing')
        ->assertNotFound();
});

test('stores the HTML ID and CSS classes of blocks as their attributes', function () {
    entryEditor()
        ->set('name', 'About')
        ->call('addBlock', 'paragraph')
        ->set('blocks.0.attributes.id', 'team')
        ->set('blocks.0.attributes.class', 'lead')
        ->call('save')
        ->assertHasNoErrors();

    $block = Entry::withAnyStatus()->sole()->content->first();

    expect($block->attributes)->toBe(['id' => 'team', 'class' => 'lead']);
});

test('cannot add blocks of unregistered types', function () {
    entryEditor()->call('addBlock', 'gallery')->assertNotFound();
});
