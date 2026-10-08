<?php

use App\EntryTypes\Page;
use App\Models\Entry;
use App\Models\Option;

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
    Option::set('page_roles', ['posts' => $entry->id], 'core');

    expect(Page::for('posts'))->toBeInstanceOf(Page::class)
        ->and(Page::for('posts')->is($entry))->toBeTrue()
        ->and(Page::for('home'))->toBeNull()
        ->and(Page::for('posts')->role())->toBe('posts')
        ->and(page_url('posts'))->toBe(url('blog'));
});

test('links the home page to the site root', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'slug' => 'home']);
    Option::set('page_roles', ['home' => $entry->id], 'core');

    expect(page_url('home'))->toBe(url('/'));
});

test('rejects a page role that is already registered', function () {
    expect(fn () => Page::registerRole('home'))
        ->toThrow(DomainException::class, 'Page role [home] is already registered.');
});
