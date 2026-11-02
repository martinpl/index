<?php

use App\Facades\Metabox;
use App\Fields\Text;
use App\Fields\Toggle;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
});

function summary(string $key): Closure
{
    return fn (?Entry $entry): string => "Box $key: ".($entry->name ?? 'new');
}

test('registers a metabox with defaults', function () {
    Metabox::register('summary', ['render' => $render = summary('summary')], 'plugins/seo');

    expect(Metabox::get('summary'))->toBe([
        'key' => 'summary',
        'render' => $render,
        'fields' => null,
        'with' => ['title' => 'Summary'],
        'location' => 'entry',
        'can' => null,
        'position' => 10,
        'owner' => 'plugins/seo',
    ]);
});

test('rejects a duplicate key naming its owner', function () {
    Metabox::register('summary', ['render' => summary('summary')], 'plugins/seo');

    Metabox::register('summary', ['render' => summary('summary')], 'core');
})->throws(DomainException::class, 'Metabox [summary] is already registered by [plugins/seo].');

test('the editor shows the entry metaboxes the user may see by position, rendering them for the entry', function () {
    Metabox::register('summary', ['render' => summary('summary'), 'with' => ['title' => 'Overview'], 'position' => 20], 'core');
    Metabox::register('first', ['render' => summary('first'), 'position' => 5], 'core');
    Metabox::register('elsewhere', ['render' => summary('elsewhere'), 'with' => ['title' => 'Stock'], 'location' => 'term'], 'core');
    Metabox::register('secret', ['render' => summary('secret'), 'with' => ['title' => 'Hidden'], 'can' => 'secrets.manage'], 'core');
    $entry = Entry::factory()->create(['type' => 'page', 'name' => 'About us']);

    Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $entry->id])
        ->assertSeeInOrder(['First', 'Box first: About us', 'Overview', 'Box summary: About us'])
        ->assertDontSee(['Stock', 'Hidden']);

    Livewire::test('pages::dashboard.entry', ['type' => 'page'])
        ->assertSee('Box summary: new');
});

test('the editor escapes rendered strings but not HTML', function () {
    Metabox::register('text', ['render' => fn (): string => '<b>Text</b>'], 'core');
    Metabox::register('html', ['render' => fn (): HtmlString => new HtmlString('<i>Html</i>')], 'core');

    Livewire::test('pages::dashboard.entry', ['type' => 'page'])
        ->assertSee('&lt;b&gt;Text&lt;/b&gt;', false)
        ->assertSee('<i>Html</i>', false);
});

test('the editor shows the metaboxes of its entry type', function () {
    Metabox::register('pages', ['render' => summary('pages'), 'with' => ['title' => 'Page only'], 'location' => 'entry.page'], 'core');
    Metabox::register('shared', ['render' => summary('shared'), 'with' => ['title' => 'Shared'], 'location' => ['entry.book', 'entry.page']], 'core');
    Metabox::register('books', ['render' => summary('books'), 'with' => ['title' => 'Book only'], 'location' => 'entry.book'], 'core');

    Livewire::test('pages::dashboard.entry', ['type' => 'page'])
        ->assertSee(['Page only', 'Shared'])
        ->assertDontSee('Book only');
});

test('the editor fills the fields of metaboxes from the meta of the entry, falling back to their defaults', function () {
    Metabox::register('seo', ['fields' => fn (): array => [
        Text::make('seo_title')->label('SEO title'),
        Toggle::make('seo_index')->default(true),
    ]], 'plugins/seo');
    $entry = Entry::factory()->create(['type' => 'page']);
    $entry->setMeta('seo_title', 'About us | Index', 'plugins/seo');

    Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $entry->id])
        ->assertSet('metaboxes', ['seo' => ['seo_title' => 'About us | Index', 'seo_index' => true]])
        ->assertSeeInOrder(['Seo', 'SEO title', 'wire:model="metaboxes.seo.seo_title"'], false);
});

test('the editor validates the fields of metaboxes and stores them as meta owned by the metabox owner', function () {
    Metabox::register('seo', ['fields' => fn (): array => [
        Text::make('seo_title')->rules('max:10'),
        Text::make('seo_description'),
    ]], 'plugins/seo');
    $entry = Entry::factory()->create(['type' => 'page']);
    $entry->setMeta('seo_description', 'Old', 'plugins/seo');

    Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $entry->id])
        ->set('metaboxes.seo.seo_title', 'Far too long a title')
        ->call('save')
        ->assertHasErrors('metaboxes.seo.seo_title')
        ->set('metaboxes.seo.seo_title', 'About us')
        ->set('metaboxes.seo.seo_description', '')
        ->set('metaboxes.seo.injected', 'ignored')
        ->call('save')
        ->assertHasNoErrors();

    expect($entry->meta()->get(['key', 'value', 'owner'])->toArray())->toBe([
        ['key' => 'seo_title', 'value' => 'About us', 'owner' => 'plugins/seo'],
    ]);
});

test('the editor does not store the fields of metaboxes the user may not see', function () {
    Metabox::register('secret', ['fields' => fn (): array => [Text::make('secret')], 'can' => 'secrets.manage'], 'core');
    $entry = Entry::factory()->create(['type' => 'page']);

    Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $entry->id])
        ->set('metaboxes.secret.secret', 'injected')
        ->call('save')
        ->assertHasNoErrors();

    expect($entry->hasMeta('secret'))->toBeFalse();
});
