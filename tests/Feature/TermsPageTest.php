<?php

use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Term;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

beforeEach(function () {
    TermType::register('category', ['entry_types' => ['page'], 'hierarchical' => true], 'core');

    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
});

function termsPage(): Testable
{
    return Livewire::test('pages::dashboard.terms', ['type' => 'category']);
}

test('requires the ability of the term type', function () {
    TermType::register('brand', ['entry_types' => ['page'], 'can' => 'terms.brand.manage'], 'core');

    get(route('dashboard.terms', 'category'))->assertOk();
    get(route('dashboard.terms', 'brand'))->assertForbidden();
    get(route('dashboard.home'))->assertSee(route('dashboard.terms', 'category'))->assertDontSee(route('dashboard.terms', 'brand'));
    get(route('dashboard.terms', 'missing'))->assertNotFound();
});

test('lists the terms of the type with their entry count', function () {
    $term = Term::factory()->create(['name' => 'News', 'slug' => 'news']);
    $term->entries()->attach(Entry::factory(2)->create(['type' => 'page']));
    Term::factory()->create(['type' => 'tag', 'name' => 'Some tag']);

    termsPage()
        ->assertSeeInOrder(['News', 'news', '2'])
        ->assertDontSee('Some tag');
});

test('adds a term, generating a unique slug from the name', function () {
    termsPage()
        ->set('values.name', 'Latest News')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Category added.')
        ->assertSet('values.name', null)
        ->assertSee('Latest News')
        ->set('values.name', 'Latest News')
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseHas('terms', ['type' => 'category', 'name' => 'Latest News', 'slug' => 'latest-news', 'owner' => 'core']);
    assertDatabaseHas('terms', ['type' => 'category', 'name' => 'Latest News', 'slug' => 'latest-news-2']);
});

test('validates the term', function () {
    Term::factory()->create(['slug' => 'news']);
    Term::factory()->create(['type' => 'tag', 'slug' => 'tag']);
    $other = Term::factory()->create(['type' => 'tag']);

    termsPage()
        ->set('values.name', '')
        ->call('save')
        ->assertHasErrors(['values.name' => 'required'])
        ->set('values.name', 'News')
        ->set('values.slug', 'news')
        ->set('values.parent_id', (string) $other->id)
        ->call('save')
        ->assertHasErrors(['values.slug' => 'unique', 'values.parent_id' => 'in'])
        ->set('values.slug', 'tag')
        ->set('values.parent_id', null)
        ->call('save')
        ->assertHasNoErrors();
});

test('edits a term in the form', function () {
    $parent = Term::factory()->create(['name' => 'Parent']);
    $term = Term::factory()->create(['name' => 'News', 'slug' => 'news']);
    $child = Term::factory()->create(['parent_id' => $term->id]);

    $page = termsPage()
        ->call('runAction', 'edit', $term->id)
        ->assertSet('editing', $term->id)
        ->assertSet('values.slug', 'news')
        ->assertSee('Update')
        ->assertViewHas('fields', fn (array $fields): bool => array_keys($fields[2]->getOptions()) === [$parent->id]);

    $page->set('values.name', 'Updates')
        ->set('values.slug', 'news')
        ->set('values.parent_id', (string) $parent->id)
        ->set('values.description', 'All updates.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Category updated.')
        ->assertSet('editing', null);

    assertDatabaseHas('terms', ['id' => $term->id, 'name' => 'Updates', 'slug' => 'news', 'parent_id' => $parent->id, 'description' => 'All updates.']);
    assertDatabaseHas('terms', ['id' => $child->id, 'parent_id' => $term->id]);
});

test('leaves out the parent of a flat type', function () {
    TermType::register('tag', ['entry_types' => ['page']], 'core');
    Term::factory()->create(['type' => 'tag']);

    Livewire::test('pages::dashboard.terms', ['type' => 'tag'])
        ->assertViewHas('fields', fn (array $fields): bool => ! in_array('parent_id', array_column($fields, 'name'), true))
        ->assertViewHas('listing', fn (array $listing): bool => ! in_array('parent', array_column($listing['columns'], 'name'), true))
        ->set('values.name', 'Laravel')
        ->set('values.parent_id', '1')
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseHas('terms', ['type' => 'tag', 'name' => 'Laravel', 'parent_id' => null]);
});

test('deletes the selected terms of the type', function () {
    $terms = Term::factory(2)->create();
    $tag = Term::factory()->create(['type' => 'tag']);

    termsPage()
        ->call('runAction', 'edit', $terms[0]->id)
        ->set('selected', [...$terms->pluck('id')->map(fn (int $id): string => (string) $id), (string) $tag->id])
        ->call('runAction', 'delete')
        ->assertDispatched('toast', title: '2 terms deleted.')
        ->assertSet('editing', null);

    assertDatabaseMissing('terms', ['type' => 'category']);
    assertDatabaseHas('terms', ['id' => $tag->id]);
});
