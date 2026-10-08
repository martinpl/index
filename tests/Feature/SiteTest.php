<?php

use App\Models\Entry;
use App\Models\Option;
use App\Models\Site;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

test('serves entries of the site matching the domain and path', function () {
    Site::create(['domain' => 'example.com']);
    Option::set('active_theme', 'theme', 'core');
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'hello', 'name' => 'Main hello']);
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'blogger', 'name' => 'Main blogger']);

    Site::create(['domain' => 'example.com', 'path' => 'blog'])->makeCurrent();
    Option::set('active_theme', 'theme', 'core');
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'hello', 'name' => 'Blog hello']);

    get('http://localhost/hello')->assertOk()->assertSee('Main hello');
    get('http://example.com/blog/hello')->assertOk()->assertSee('Blog hello');
    get('http://localhost/blogger')->assertOk()->assertSee('Main blogger');
    get('http://example.com/blog/missing')->assertNotFound();
});

test('uses its own tables', function () {
    Site::create(['domain' => 'blog.example.com'])->makeCurrent();

    User::factory()->create(['email' => 'taylor@example.com']);

    assertDatabaseHas('site_2_users', ['email' => 'taylor@example.com']);
    assertDatabaseMissing('users', ['email' => 'taylor@example.com']);
    expect(config('cache.stores.database.table'))->toBe('site_2_cache')
        ->and(config('session.table'))->toBe('site_2_sessions')
        ->and(config('queue.connections.database.table'))->toBe('site_2_jobs');
});

test('boots plugins active on the requested site only', function () {
    Site::create(['domain' => 'example.com']);
    Site::create(['domain' => 'blog.example.com'])->makeCurrent();
    Option::set('active_plugins', ['hello-index'], 'core');

    get('http://example.com/hello-index')->assertNotFound();
    get('http://blog.example.com/hello-index')->assertOk();
});
