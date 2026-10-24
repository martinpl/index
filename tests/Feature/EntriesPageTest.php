<?php

use App\Enums\EntryStatus;
use App\Facades\EntryType;
use App\Models\Entry;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(entryManager());
});

function entryManager(): User
{
    return tap(User::factory()->create())->grantAbilities(['entries.manage']);
}

function entriesPage(): Testable
{
    return Livewire::test('pages::dashboard.entries', ['type' => 'page']);
}

test('users without the ability cannot manage entries', function () {
    actingAs(User::factory()->create());

    get(route('dashboard.entries', 'page'))->assertForbidden();
    get(route('dashboard.home'))->assertDontSee(route('dashboard.entries', 'page'));
});

test('requires the ability of the entry type', function () {
    EntryType::register('product', ['can' => 'entries.product.manage'], 'core');

    get(route('dashboard.entries', 'product'))->assertForbidden();
    get(route('dashboard.home'))->assertDontSee(route('dashboard.entries', 'product'));

    actingAs(tap(User::factory()->create())->grantAbilities(['entries.product.manage']));

    get(route('dashboard.entries', 'product'))->assertOk();
    get(route('dashboard.entries', 'page'))->assertForbidden();
});

test('lists the entries of the type except trashed ones', function () {
    Entry::factory()->published()->create(['type' => 'page', 'name' => 'About us']);
    Entry::factory()->create(['type' => 'page', 'name' => 'Draft page']);
    Entry::factory()->create(['type' => 'page', 'name' => 'Trashed page', 'status' => EntryStatus::Trash]);
    Entry::factory()->create(['type' => 'post', 'name' => 'Some post']);

    entriesPage()
        ->assertSeeText(['About us', 'Draft page — Draft'])
        ->assertDontSeeText('About us — Draft')
        ->assertDontSee(['Trashed page', 'Some post'])
        ->set('tab', 'trash')
        ->assertSee('Trashed page')
        ->assertDontSee(['About us', 'Draft page'])
        ->set('tab', 'draft')
        ->assertSee('Draft page')
        ->assertDontSeeText('Draft page — Draft');
});

test('searches and sorts the entries of the tab', function () {
    Entry::factory()->published()->create(['type' => 'page', 'name' => 'Beta']);
    Entry::factory()->published()->create(['type' => 'page', 'name' => 'Alpha']);
    Entry::factory()->create(['type' => 'page', 'name' => 'Alpha draft']);

    entriesPage()
        ->set('search', 'alpha')
        ->assertSee(['Alpha', 'Alpha draft'])
        ->assertDontSee('Beta')
        ->set('tab', 'publish')
        ->assertDontSee('Alpha draft')
        ->set('search', '')
        ->call('sortBy', 'name')
        ->assertSeeInOrder(['Alpha', 'Beta'])
        ->call('sortBy', 'name')
        ->assertSeeInOrder(['Beta', 'Alpha']);
});

test('ignores unsupported sorting and tabs', function () {
    Entry::factory()->create(['type' => 'page', 'name' => 'Trashed page', 'status' => EntryStatus::Trash]);

    entriesPage()
        ->call('sortBy', 'user')
        ->assertSet('sort', null)
        ->set('tab', 'unknown')
        ->assertDontSee('Trashed page');
});

test('shows the tabs with their counts, hiding empty ones', function () {
    $user = entryManager();
    actingAs($user);
    Entry::factory()->published()->create(['type' => 'page', 'user_id' => $user->id]);
    Entry::factory()->published()->create(['type' => 'page']);
    Entry::factory()->create(['type' => 'page', 'user_id' => $user->id]);

    entriesPage()
        ->assertSeeInOrder(['All', '3', 'Mine', '2', 'Published', '2', 'Drafts', '1'])
        ->assertDontSeeHtml('listing-tab-trash');

    entriesPage()->set('tab', 'mine')->assertViewHas('listing', fn (array $listing): bool => $listing['records']->total() === 2);
});

test('toggles the hideable columns', function () {
    entriesPage()
        ->assertSet('hiddenColumns', ['modified'])
        ->call('toggleColumn', 'modified')
        ->assertSet('hiddenColumns', [])
        ->call('toggleColumn', 'user')
        ->assertSet('hiddenColumns', ['user'])
        ->call('toggleColumn', 'name')
        ->assertSet('hiddenColumns', ['user']);
});

test('moves an entry to the trash, restores and deletes it', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page']);

    entriesPage()
        ->call('runAction', 'delete', $entry->id)
        ->call('runAction', 'trash', $entry->id)
        ->assertDispatched('toast', title: '1 entry moved to the trash.');

    expect($entry->refresh()->status)->toBe(EntryStatus::Trash->value);

    entriesPage()
        ->set('tab', 'trash')
        ->call('runAction', 'restore', $entry->id);

    expect($entry->refresh()->status)->toBe(EntryStatus::Draft->value);

    $entry->delete();

    entriesPage()
        ->set('tab', 'trash')
        ->call('runAction', 'delete', $entry->id);

    assertDatabaseMissing('entries', ['id' => $entry->id]);
});

test('runs a bulk action on the selected entries of the type', function () {
    $pages = Entry::factory(2)->create(['type' => 'page']);
    $post = Entry::factory()->create(['type' => 'post']);

    entriesPage()
        ->set('selected', [...$pages->pluck('id')->map(fn (int $id): string => (string) $id), (string) $post->id])
        ->assertSee('Move to trash')
        ->assertDontSee(['Restore', 'Delete permanently'])
        ->call('runAction', 'trash')
        ->assertSet('selected', [])
        ->assertDispatched('toast', title: '2 entries moved to the trash.')
        ->set('tab', 'trash')
        ->assertSeeHtml(['data-value="restore"', 'data-value="delete"'])
        ->assertDontSeeHtml('data-value="trash"');

    expect(Entry::withAnyStatus()->where('status', EntryStatus::Trash)->pluck('id')->all())->toBe($pages->pluck('id')->all());
    assertDatabaseHas('entries', ['id' => $post->id, 'status' => EntryStatus::Draft->value]);
});

test('rejects unknown actions and running a single entry action in bulk', function () {
    entriesPage()->call('runAction', 'missing', 1)->assertNotFound();
    entriesPage()->call('runAction', 'view')->assertNotFound();
});
