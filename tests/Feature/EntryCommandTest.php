<?php

use App\Facades\EntryType;
use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    EntryType::register('post');
    TermType::register('category', ['entry_types' => ['post']]);
    TermType::register('tag', ['entry_types' => ['post']]);
});

test('creates an entry', function () {
    $user = User::factory()->create();
    $parent = Entry::factory()->create(['type' => 'post']);

    artisan('entry:create', ['type' => 'post', 'name' => 'Hello World', '--content' => 'Welcome to Index.', '--status' => 'publish', '--user' => $user->id, '--parent' => $parent->id, '--order' => 3])
        ->expectsOutput('Created entry [2].')
        ->assertSuccessful();

    assertDatabaseHas('entries', [
        'id' => 2,
        'type' => 'post',
        'name' => 'Hello World',
        'slug' => 'hello-world',
        'content' => 'Welcome to Index.',
        'status' => 'publish',
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'order' => 3,
    ]);

    expect(Entry::find(2)->date)->not->toBeNull();
});

test('creates a draft entry by default', function () {
    artisan('entry:create', ['type' => 'post', 'name' => 'Hello World'])
        ->assertSuccessful();

    assertDatabaseHas('entries', ['name' => 'Hello World', 'status' => 'draft', 'user_id' => null, 'parent_id' => null, 'order' => 0]);
});

test('does not create an entry of an unknown type', function () {
    artisan('entry:create', ['type' => 'product', 'name' => 'Hello World'])
        ->expectsOutput('Entry type [product] does not exist.')
        ->assertFailed();

    assertDatabaseMissing('entries', ['name' => 'Hello World']);
});

test('does not create an entry with a duplicate slug', function () {
    Entry::factory()->create(['type' => 'post', 'slug' => 'hello-world']);

    artisan('entry:create', ['type' => 'post', 'name' => 'Hello World'])
        ->expectsOutput('The slug has already been taken.')
        ->assertFailed();

    artisan('entry:create', ['type' => 'page', 'name' => 'Hello World'])
        ->assertSuccessful();
});

test('gets an entry', function () {
    $user = User::factory()->create();
    $parent = Entry::factory()->create();
    $entry = Entry::factory()->published()->create(['user_id' => $user->id, 'parent_id' => $parent->id, 'order' => 2])->fresh();

    artisan('entry:get', ['entry' => $entry->id])
        ->expectsTable(['field', 'value'], [
            ['id', $entry->id],
            ['type', $entry->type],
            ['status', 'publish'],
            ['slug', $entry->slug],
            ['name', $entry->name],
            ['content', $entry->content],
            ['user', $user->id],
            ['parent', $parent->id],
            ['order', 2],
            ['date', $entry->date],
            ['modified', $entry->modified],
        ])
        ->assertSuccessful();

    artisan('entry:get', ['entry' => 999])
        ->expectsOutput('Entry [999] does not exist.')
        ->assertFailed();
});

test('lists entries', function () {
    $post = Entry::factory()->published()->create(['type' => 'post'])->fresh();
    $page = Entry::factory()->published()->create(['type' => 'page'])->fresh();
    $draft = Entry::factory()->create(['type' => 'post'])->fresh();
    Entry::factory()->create(['status' => 'trash']);

    artisan('entry:list')
        ->expectsTable(['id', 'type', 'status', 'slug', 'name', 'date'], [
            [$post->id, 'post', 'publish', $post->slug, $post->name, $post->date],
            [$page->id, 'page', 'publish', $page->slug, $page->name, $page->date],
        ])
        ->assertSuccessful();

    artisan('entry:list', ['--type' => 'page'])
        ->expectsTable(['id', 'type', 'status', 'slug', 'name', 'date'], [
            [$page->id, 'page', 'publish', $page->slug, $page->name, $page->date],
        ])
        ->assertSuccessful();

    artisan('entry:list', ['--status' => 'draft'])
        ->expectsTable(['id', 'type', 'status', 'slug', 'name', 'date'], [
            [$draft->id, 'post', 'draft', $draft->slug, $draft->name, $draft->date],
        ])
        ->assertSuccessful();

    artisan('entry:list', ['--status' => 'archived'])
        ->expectsOutput('No entries found.')
        ->assertSuccessful();
});

test('queries only published entries by default', function () {
    $published = Entry::factory()->published()->create();
    Entry::factory()->create();
    Entry::factory()->create(['status' => 'trash']);

    expect(Entry::pluck('id')->all())->toBe([$published->id])
        ->and(Entry::withAnyStatus()->count())->toBe(3);
});

test('updates an entry', function () {
    $user = User::factory()->create();
    $parent = Entry::factory()->create(['type' => 'post']);
    $entry = Entry::factory()->create(['type' => 'post']);

    artisan('entry:update', ['entry' => $entry->id, '--name' => 'Updated', '--slug' => 'updated', '--status' => 'publish', '--user' => $user->id, '--parent' => $parent->id, '--order' => 5])
        ->expectsOutput("Updated entry [$entry->id].")
        ->assertSuccessful();

    $entry->refresh();

    expect($entry->name)->toBe('Updated')
        ->and($entry->slug)->toBe('updated')
        ->and($entry->status)->toBe('publish')
        ->and($entry->user_id)->toBe($user->id)
        ->and($entry->parent_id)->toBe($parent->id)
        ->and($entry->order)->toBe(5);
});

test('does not make an entry its own parent', function () {
    $entry = Entry::factory()->create(['type' => 'page']);

    artisan('entry:update', ['entry' => $entry->id, '--parent' => $entry->id])
        ->expectsOutput('The selected parent is invalid.')
        ->assertFailed();
});

test('restores a trashed entry', function () {
    $entry = Entry::factory()->create(['status' => 'trash']);

    artisan('entry:update', ['entry' => $entry->id, '--status' => 'draft'])
        ->assertSuccessful();

    expect($entry->refresh()->status)->toBe('draft');
});

test('requires fields to update an entry', function () {
    $entry = Entry::factory()->create();

    artisan('entry:update', ['entry' => $entry->id])
        ->expectsOutput('No fields to update.')
        ->assertFailed();
});

test('trashes and deletes an entry', function () {
    $entry = Entry::factory()->create();

    artisan('entry:delete', ['entry' => $entry->id])
        ->expectsOutput("Trashed entry [$entry->id].")
        ->assertSuccessful();

    assertDatabaseHas('entries', ['id' => $entry->id, 'status' => 'trash']);

    artisan('entry:delete', ['entry' => $entry->id])
        ->expectsOutput("Deleted entry [$entry->id].")
        ->assertSuccessful();

    assertDatabaseMissing('entries', ['id' => $entry->id]);
});

test('force deletes an entry without trashing it', function () {
    $entry = Entry::factory()->create();

    artisan('entry:delete', ['entry' => $entry->id, '--force' => true])
        ->expectsOutput("Deleted entry [$entry->id].")
        ->assertSuccessful();

    assertDatabaseMissing('entries', ['id' => $entry->id]);
});

test('manages the terms of an entry', function () {
    $entry = Entry::factory()->create(['type' => 'post']);
    $news = Term::factory()->create(['type' => 'category', 'name' => 'News', 'slug' => 'news']);
    $tech = Term::factory()->create(['type' => 'category', 'name' => 'Tech', 'slug' => 'tech']);
    $laravel = Term::factory()->create(['type' => 'tag', 'name' => 'Laravel', 'slug' => 'laravel']);

    artisan('entry:term', ['action' => 'add', 'entry' => $entry->id, 'type' => 'category', 'terms' => ['news', 'tech']])
        ->expectsOutput("Updated [category] terms of entry [$entry->id].")
        ->assertSuccessful();

    artisan('entry:term', ['action' => 'add', 'entry' => $entry->id, 'type' => 'tag', 'terms' => ['laravel']])
        ->assertSuccessful();

    artisan('entry:term', ['action' => 'list', 'entry' => $entry->id, 'type' => 'category'])
        ->expectsTable(['id', 'slug', 'name'], [
            [$news->id, 'news', 'News'],
            [$tech->id, 'tech', 'Tech'],
        ])
        ->assertSuccessful();

    artisan('entry:term', ['action' => 'remove', 'entry' => $entry->id, 'type' => 'category', 'terms' => ['news']])
        ->assertSuccessful();

    expect($entry->terms()->pluck('slug')->sort()->values()->all())->toBe(['laravel', 'tech']);

    artisan('entry:term', ['action' => 'set', 'entry' => $entry->id, 'type' => 'category', 'terms' => ['news']])
        ->assertSuccessful();

    expect($entry->terms()->pluck('slug')->sort()->values()->all())->toBe(['laravel', 'news']);

    artisan('entry:term', ['action' => 'set', 'entry' => $entry->id, 'type' => 'category'])
        ->assertSuccessful();

    expect($entry->terms()->pluck('slug')->all())->toBe(['laravel']);
});

test('does not assign missing terms to an entry', function () {
    $entry = Entry::factory()->create(['type' => 'post']);

    artisan('entry:term', ['action' => 'add', 'entry' => $entry->id, 'type' => 'category', 'terms' => ['missing']])
        ->expectsOutput('Term [missing] does not exist.')
        ->assertFailed();
});

test('does not assign terms of an unrelated term type to an entry', function () {
    $entry = Entry::factory()->create(['type' => 'page']);

    artisan('entry:term', ['action' => 'list', 'entry' => $entry->id, 'type' => 'category'])
        ->expectsOutput('Term type [category] is not registered for entry type [page].')
        ->assertFailed();
});

test('manages the meta of an entry', function () {
    $entry = Entry::factory()->create();
    $entry->setMeta('tags', ['laravel', 'index']);

    artisan('entry:meta', ['action' => 'set', 'entry' => $entry->id, 'key' => 'color', 'value' => 'red'])
        ->expectsOutput("Set meta [color] of entry [$entry->id].")
        ->assertSuccessful();

    artisan('entry:meta', ['action' => 'get', 'entry' => $entry->id, 'key' => 'color'])
        ->expectsOutput('red')
        ->assertSuccessful();

    artisan('entry:meta', ['action' => 'list', 'entry' => $entry->id])
        ->expectsTable(['key', 'value'], [
            ['color', 'red'],
            ['tags', '["laravel","index"]'],
        ])
        ->assertSuccessful();

    artisan('entry:meta', ['action' => 'delete', 'entry' => $entry->id, 'key' => 'color'])
        ->expectsOutput("Deleted meta [color] of entry [$entry->id].")
        ->assertSuccessful();

    expect($entry->hasMeta('color'))->toBeFalse();

    artisan('entry:meta', ['action' => 'get', 'entry' => $entry->id, 'key' => 'color'])
        ->expectsOutput("Meta [color] of entry [$entry->id] does not exist.")
        ->assertFailed();
});
