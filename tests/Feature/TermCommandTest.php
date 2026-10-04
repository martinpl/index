<?php

use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Term;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    TermType::register('category');
    TermType::register('tag');
});

test('creates a term', function () {
    $parent = Term::factory()->create(['type' => 'category']);

    artisan('term:create', ['type' => 'category', 'name' => 'Laravel News', '--description' => 'News about Laravel.', '--parent' => $parent->id])
        ->expectsOutput('Created term [2].')
        ->assertSuccessful();

    assertDatabaseHas('terms', [
        'id' => 2,
        'type' => 'category',
        'name' => 'Laravel News',
        'slug' => 'laravel-news',
        'description' => 'News about Laravel.',
        'parent_id' => $parent->id,
    ]);
});

test('does not create a term of an unknown type', function () {
    artisan('term:create', ['type' => 'genre', 'name' => 'Rock'])
        ->expectsOutput('Term type [genre] does not exist.')
        ->assertFailed();
});

test('does not create a term with a parent of another type', function () {
    $tag = Term::factory()->create(['type' => 'tag']);

    artisan('term:create', ['type' => 'category', 'name' => 'News', '--parent' => $tag->id])
        ->expectsOutput('The selected parent is invalid.')
        ->assertFailed();
});

test('gets a term', function () {
    $term = Term::factory()->create(['type' => 'category', 'name' => 'News', 'slug' => 'news']);

    artisan('term:get', ['type' => 'category', 'term' => $term->id])
        ->expectsTable(['field', 'value'], [
            ['id', $term->id],
            ['type', 'category'],
            ['slug', 'news'],
            ['name', 'News'],
            ['description', null],
            ['parent', null],
        ])
        ->assertSuccessful();

    artisan('term:get', ['type' => 'tag', 'term' => $term->id])
        ->expectsOutput("Term [$term->id] does not exist.")
        ->assertFailed();
});

test('lists terms', function () {
    $news = Term::factory()->create(['type' => 'category', 'name' => 'News', 'slug' => 'news']);
    $laravel = Term::factory()->create(['type' => 'category', 'name' => 'Laravel', 'slug' => 'laravel', 'parent_id' => $news->id]);
    Term::factory()->create(['type' => 'tag']);

    Entry::factory()->published()->create()->terms()->attach($news);
    Entry::factory()->create()->terms()->attach($news);
    Entry::factory()->create(['status' => 'trash'])->terms()->attach($news);

    artisan('term:list', ['type' => 'category'])
        ->expectsTable(['id', 'slug', 'name', 'parent', 'count'], [
            [$laravel->id, 'laravel', 'Laravel', $news->id, 0],
            [$news->id, 'news', 'News', null, 1],
        ])
        ->assertSuccessful();
});

test('updates a term', function () {
    $term = Term::factory()->create(['type' => 'category']);

    artisan('term:update', ['type' => 'category', 'term' => $term->id, '--name' => 'News', '--slug' => 'news'])
        ->expectsOutput("Updated term [$term->id].")
        ->assertSuccessful();

    assertDatabaseHas('terms', ['id' => $term->id, 'name' => 'News', 'slug' => 'news']);
});

test('does not make a term its own parent', function () {
    $term = Term::factory()->create(['type' => 'category']);

    artisan('term:update', ['type' => 'category', 'term' => $term->id, '--parent' => $term->id])
        ->expectsOutput('The selected parent is invalid.')
        ->assertFailed();
});

test('deletes a term', function () {
    $term = Term::factory()->create(['type' => 'category']);
    $entry = Entry::factory()->create();
    $entry->terms()->attach($term);

    artisan('term:delete', ['type' => 'category', 'term' => $term->id])
        ->expectsOutput("Deleted term [$term->id].")
        ->assertSuccessful();

    assertDatabaseMissing('terms', ['id' => $term->id]);
    assertDatabaseMissing('entry_term', ['term_id' => $term->id]);
});

test('manages the meta of a term', function () {
    $term = Term::factory()->create(['type' => 'category']);

    artisan('term:meta', ['action' => 'list', 'type' => 'category', 'term' => $term->id])
        ->expectsOutput('No meta found.')
        ->assertSuccessful();

    artisan('term:meta', ['action' => 'set', 'type' => 'category', 'term' => $term->id, 'key' => 'color', 'value' => 'red'])
        ->expectsOutput("Set meta [color] of term [$term->id].")
        ->assertSuccessful();

    artisan('term:meta', ['action' => 'get', 'type' => 'category', 'term' => $term->id, 'key' => 'color'])
        ->expectsOutput('red')
        ->assertSuccessful();

    artisan('term:meta', ['action' => 'delete', 'type' => 'category', 'term' => $term->id, 'key' => 'color'])
        ->expectsOutput("Deleted meta [color] of term [$term->id].")
        ->assertSuccessful();

    expect($term->hasMeta('color'))->toBeFalse();

    artisan('term:meta', ['action' => 'list', 'type' => 'tag', 'term' => $term->id])
        ->expectsOutput("Term [$term->id] does not exist.")
        ->assertFailed();
});
