<?php

use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;

beforeEach(function () {
    Site::find(1)->makeCurrent();

    actingAs(tap(User::factory()->create())->grantAbilities(['sites.manage']));
});

function sitesPage(): Testable
{
    return Livewire::test('pages::dashboard.sites');
}

test('users without the ability cannot manage sites', function () {
    get(route('dashboard.sites'))->assertOk();
    get(route('dashboard.home'))->assertSee(route('dashboard.sites'));

    actingAs(User::factory()->create());

    get(route('dashboard.sites'))->assertForbidden();
    get(route('dashboard.home'))->assertDontSee(route('dashboard.sites'));
});

test('sites are managed from the main site only', function () {
    Site::create(['domain' => 'blog.example.com'])->makeCurrent();

    actingAs(tap(User::factory()->create())->grantAbilities(['sites.manage']));

    get('http://blog.example.com/dashboard/sites')->assertForbidden();
    get('http://blog.example.com/dashboard')->assertDontSee('dashboard/sites');
});

test('does not link to the sites from other sites, even for users managing them', function () {
    $email = auth()->user()->email;
    Site::create(['domain' => 'blog.example.com'])->makeCurrent();

    actingAs(User::factory()->create(['email' => $email]));

    get('http://blog.example.com/dashboard')->assertDontSee('aria-label="Sites"', false);
});

test('lists the sites with their names and addresses', function () {
    Setting::set('site_name', 'Main', 'core');
    Site::create(['domain' => 'example.com', 'path' => 'blog'])
        ->execute(fn () => Setting::set('site_name', 'Blog', 'core'));

    sitesPage()->assertSeeTextInOrder(['Main — Main site', 'localhost', '/', 'Blog', 'example.com', '/blog']);

    get('http://localhost/dashboard/sites')->assertSee(['href="http://example.com/blog"', 'href="http://example.com/blog/dashboard"'], false);
});

test('escapes the names of the sites', function () {
    Site::create(['domain' => 'example.com'])
        ->execute(fn () => Setting::set('site_name', '<script>alert(1)</script>', 'core'));

    sitesPage()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('deletes the selected sites with their tables, except the main site', function () {
    $sites = Site::factory(2)->create();

    sitesPage()
        ->set('selected', ['1', ...$sites->map(fn (Site $site): string => (string) $site->id)])
        ->call('runAction', 'delete')
        ->assertDispatched('toast', title: '2 sites deleted.');

    $sites->each(fn (Site $site) => assertModelMissing($site));
    assertModelExists(Site::find(1));
    expect(Schema::hasTable("site_{$sites[0]->id}_entries"))->toBeFalse();
});
