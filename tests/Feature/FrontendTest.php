<?php

use App\Facades\EntryType;
use App\Facades\Filter;
use App\Models\Entry;
use App\Models\Setting;
use Illuminate\View\ComponentAttributeBag;

use function Pest\Laravel\get;

beforeEach(function (): void {
    Setting::set('active_theme', 'howdy', 'core');
});

test('returns not found without a home entry', function () {
    get('/')->assertNotFound();
});

test('renders the home entry', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'name' => 'Home']);
    Setting::set('page_roles', ['home' => $entry->id], 'core');

    get('/')->assertOk()->assertViewIs('index')->assertSee('Home');
});

test('themes and plugins can change the attributes of the body', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'name' => 'Home']);
    Setting::set('page_roles', ['home' => $entry->id], 'core');
    Filter::add('body.attributes', fn (ComponentAttributeBag $attributes): ComponentAttributeBag => $attributes->class('dark')->merge(['data-page' => 'home']));

    get('/')->assertOk()->assertSee('<body data-page="home" class="dark blocks">', false);
});

test('serves no frontend without an active theme', function () {
    Setting::forget('active_theme');
    $entry = Entry::factory()->published()->create(['type' => 'page', 'slug' => 'home']);
    Setting::set('page_roles', ['home' => $entry->id], 'core');

    get('/')->assertNotFound();
    get('/home')->assertNotFound();
});

test('returns not found when entry is not published', function () {
    $entry = Entry::factory()->create(['type' => 'page', 'status' => 'draft']);
    Setting::set('page_roles', ['home' => $entry->id], 'core');

    get('/')->assertNotFound();
});

test('renders an entry under its entry type slug', function () {
    EntryType::register('post', [], 'core');
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'about', 'name' => 'About']);
    Entry::factory()->published()->create(['type' => 'post', 'slug' => 'hello', 'name' => 'Hello']);

    get('/about')->assertOk()->assertSee('About');
    get('/posts/hello')->assertOk()->assertSee('Hello');
    get('/hello')->assertNotFound();
    get('/posts/about')->assertNotFound();
});

test('renders an entry under a custom entry type slug', function () {
    EntryType::register('product', ['slug' => 'shop/products'], 'core');
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
