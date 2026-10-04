<?php

use App\Facades\EntryType;
use App\Models\Entry;
use App\Models\Option;

use function Pest\Laravel\get;

beforeEach(function (): void {
    Option::set('active_theme', 'theme');
});

test('returns not found without a home entry', function () {
    get('/')->assertNotFound();
});

test('renders the home entry', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'name' => 'Home']);
    Option::set('page_roles', ['home' => $entry->id]);

    get('/')->assertOk()->assertViewIs('index')->assertSee('Home');
});

test('returns not found when entry is not published', function () {
    $entry = Entry::factory()->create(['type' => 'page', 'status' => 'draft']);
    Option::set('page_roles', ['home' => $entry->id]);

    get('/')->assertNotFound();
});

test('renders an entry under its entry type slug', function () {
    EntryType::register('post');
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'about', 'name' => 'About']);
    Entry::factory()->published()->create(['type' => 'post', 'slug' => 'hello', 'name' => 'Hello']);

    get('/about')->assertOk()->assertSee('About');
    get('/posts/hello')->assertOk()->assertSee('Hello');
    get('/hello')->assertNotFound();
    get('/posts/about')->assertNotFound();
});

test('renders an entry under a custom entry type slug', function () {
    EntryType::register('product', ['slug' => 'shop/products']);
    Entry::factory()->published()->create(['type' => 'product', 'slug' => 'chair', 'name' => 'Chair']);

    get('/shop/products/chair')->assertOk()->assertSee('Chair');
    get('/products/chair')->assertNotFound();
});

test('renders a nested entry under its parents', function () {
    $parent = Entry::factory()->published()->create(['type' => 'page', 'slug' => 'company']);
    $child = Entry::factory()->published()->create(['type' => 'page', 'slug' => 'team', 'parent_id' => $parent->id]);
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'history', 'name' => 'History', 'parent_id' => $child->id]);

    get('/company/team/history')->assertOk()->assertSee('History');
    get('/history')->assertNotFound();
    get('/team/history')->assertNotFound();
});

test('returns not found when a parent is not published', function () {
    $parent = Entry::factory()->create(['type' => 'page', 'slug' => 'company', 'status' => 'draft']);
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'team', 'parent_id' => $parent->id]);

    get('/company/team')->assertNotFound();
});

test('redirects non-canonical urls permanently', function () {
    get('/Posts/Hello?Ref=X')
        ->assertMovedPermanently()
        ->assertRedirect('/posts/hello?Ref=X');

    get('http://localhost/posts/hello/?page=2')
        ->assertMovedPermanently()
        ->assertRedirect('http://localhost/posts/hello?page=2');

    get('http://localhost/Posts/Hello/')
        ->assertMovedPermanently()
        ->assertRedirect('http://localhost/posts/hello');
});
