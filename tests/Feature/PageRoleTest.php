<?php

use App\EntryTypes\Page;
use App\Models\Entry;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('registers page roles', function () {
    Page::registerRole('shop', 'Shop page');
    Page::registerRole('cart');

    expect(Page::roles())->toBe([
        'home' => 'Home page',
        'shop' => 'Shop page',
        'cart' => 'Cart',
    ]);
});

test('finds the published page assigned to a role', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'slug' => 'blog']);
    Setting::set('page_roles', ['posts' => $entry->id], 'core');

    expect(Page::for('posts'))->toBeInstanceOf(Page::class)
        ->and(Page::for('posts')->is($entry))->toBeTrue()
        ->and(Page::for('home'))->toBeNull()
        ->and(Page::for('posts')->role())->toBe('posts')
        ->and(page_url('posts'))->toBe(url('blog'));
});

test('links the home page to the site root', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'slug' => 'home']);
    Setting::set('page_roles', ['home' => $entry->id], 'core');

    expect(page_url('home'))->toBe(url('/'));
});

test('rejects a page role that is already registered', function () {
    expect(fn () => Page::registerRole('home'))
        ->toThrow(DomainException::class, 'Page role [home] is already registered.');
});

test('the page editor assigns the page to a role, taking the role from the page holding it', function () {
    actingAs(tap(User::factory()->create())->grantAbilities(['entries.manage']));
    $previous = Entry::factory()->published()->create(['type' => 'page']);
    Setting::set('page_roles', ['home' => $previous->id, 'shop' => $previous->id], 'core');

    Livewire::test('pages::dashboard.entry', ['type' => 'page'])
        ->assertSeeInOrder(['Role', 'Home page'])
        ->assertDontSee('Page role')
        ->set('name', 'Welcome')
        ->set('metaboxes.page-role.role', 'home')
        ->call('save')
        ->assertHasNoErrors();

    $page = Entry::withAnyStatus()->where('name', 'Welcome')->sole();

    expect(Setting::get('page_roles'))->toBe(['home' => $page->id, 'shop' => $previous->id]);

    Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $previous->id])
        ->assertSet('metaboxes.page-role.role', null);

    Livewire::test('pages::dashboard.entry', ['type' => 'page', 'entry' => $page->id])
        ->assertSet('metaboxes.page-role.role', 'home')
        ->set('metaboxes.page-role.role', 'missing')
        ->call('save')
        ->assertHasErrors('metaboxes.page-role.role')
        ->set('metaboxes.page-role.role', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('page_roles'))->toBe(['shop' => $previous->id]);
});
