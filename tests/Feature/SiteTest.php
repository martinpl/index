<?php

use App\Models\Entry;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

test('serves entries of the site matching the domain and path', function () {
    Site::create(['domain' => 'example.com']);
    Setting::set('active_theme', 'howdy', 'core');
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'hello', 'name' => 'Main hello']);
    Entry::factory()->published()->create(['type' => 'page', 'slug' => 'blogger', 'name' => 'Main blogger']);

    Site::create(['domain' => 'example.com', 'path' => 'blog'])->makeCurrent();
    Setting::set('active_theme', 'howdy', 'core');
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
    Setting::set('active_plugins', ['hello-index'], 'core');

    get('http://example.com/hello-index')->assertNotFound();
    get('http://blog.example.com/hello-index')->assertOk();
});

test('uses its own locale', function () {
    $site = Site::create(['domain' => 'blog.example.com'])->makeCurrent();
    Setting::set('locale', 'pl', 'core');

    $site->makeCurrent();

    expect(app()->getLocale())->toBe('pl');
});

test('shows dates in its own timezone', function () {
    Setting::set('timezone', 'Europe/Warsaw', 'core');

    $date = now()->setDate(2026, 11, 7)->setTime(23, 30);

    expect($date->inTimezone()->format('Y-m-d H:i'))->toBe('2026-11-08 00:30')
        ->and($date->timezoneName)->toBe('UTC');
});

test('formats dates in its own timezone, formats and language', function () {
    Setting::set('timezone', 'Europe/Warsaw', 'core');
    Setting::set('date_format', 'j F Y', 'core');
    Setting::set('time_format', 'H:i', 'core');
    app()->setLocale('pl');

    $date = now()->setDate(2026, 11, 7)->setTime(23, 30);

    expect($date->toDateFormat())->toBe('8 listopada 2026')
        ->and($date->toTimeFormat())->toBe('00:30')
        ->and($date->toDateTimeFormat())->toBe('8 listopada 2026 00:30');
});
