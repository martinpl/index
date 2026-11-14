<?php

use App\Models\Setting;
use App\Models\Site;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('site_name', 'Main', 'core');

    $this->admin = tap(User::factory()->create(['email' => 'taylor@example.com']))->grantAbilities(['sites.manage']);

    $this->blog = Site::create(['domain' => 'example.com', 'path' => 'blog']);
    $this->blog->execute(fn () => Setting::set('site_name', 'Blog', 'core'));
});

test('lists every site with its dashboard and front page for users managing the sites', function () {
    actingAs($this->admin);

    get('http://localhost/dashboard')->assertSeeInOrder([
        'aria-label="My Sites"',
        'Main',
        'href="http://localhost/dashboard"',
        'href="http://localhost"',
        'Blog',
        'href="http://example.com/blog/dashboard"',
        'href="http://example.com/blog"',
    ], false);
});

test('is not added when there is only one site', function () {
    $this->blog->delete();
    actingAs($this->admin);

    get('http://localhost/dashboard')->assertDontSee('aria-label="My Sites"', false);
});

test('is hidden from users who cannot manage the sites', function () {
    actingAs(User::factory()->create());

    get('http://localhost/dashboard')->assertDontSee('href="http://example.com/blog/dashboard"', false);
});

test('is shown on other sites to users whose account on the main site manages the sites', function () {
    $this->blog->makeCurrent();
    actingAs(User::factory()->create(['email' => 'taylor@example.com']));

    get('http://example.com/blog/dashboard')->assertSeeInOrder([
        'href="http://localhost/dashboard"',
        'href="http://example.com/blog/dashboard"',
        'href="http://localhost/dashboard/sites"',
    ], false);
});

test('is hidden on other sites from users managing the sites on that site only', function () {
    $this->blog->makeCurrent();
    actingAs(tap(User::factory()->create())->grantAbilities(['sites.manage']));

    get('http://example.com/blog/dashboard')->assertDontSee('href="http://localhost/dashboard"', false);
});

test('escapes the names of the sites', function () {
    $this->blog->execute(fn () => Setting::set('site_name', '<script>alert(1)</script>', 'core'));
    actingAs($this->admin);

    get('http://localhost/dashboard')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});
