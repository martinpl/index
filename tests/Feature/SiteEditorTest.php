<?php

use App\EntryTypes\Page;
use App\Facades\Role;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

beforeEach(function () {
    Site::find(1)->makeCurrent();

    actingAs(tap(User::factory()->create(['name' => 'Taylor', 'email' => 'taylor@example.com']))->grantAbilities(['sites.manage']));
});

test('users without the ability cannot add or edit sites', function () {
    $site = Site::factory()->create();

    actingAs(User::factory()->create());

    get(route('dashboard.sites.create'))->assertForbidden();
    get(route('dashboard.sites.edit', $site))->assertForbidden();
});

test('returns not found for a missing site', function () {
    get(route('dashboard.sites.edit', 999))->assertNotFound();
});

test('adds and installs a site with its own administrator, offering the current user', function () {
    Livewire::test('pages::dashboard.site')
        ->assertSet('values.admin', ['name' => 'Taylor', 'email' => 'taylor@example.com', 'password' => null])
        ->set('values.domain', 'example.com')
        ->set('values.path', 'blog/')
        ->set('values.name', 'My blog')
        ->set('values.admin.password', 'secret-password')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Site added.')
        ->assertRedirect(route('dashboard.sites.edit', 2));

    assertDatabaseHas('sites', ['id' => 2, 'domain' => 'example.com', 'path' => '/blog']);

    Site::find(2)->execute(function () {
        $admin = User::sole();

        expect($admin->email)->toBe('taylor@example.com')
            ->and(Hash::check('secret-password', $admin->password))->toBeTrue()
            ->and($admin->hasVerifiedEmail())->toBeTrue()
            ->and($admin->getRoles())->toBe(['administrator'])
            ->and(Role::abilities('administrator'))->not->toContain('sites.manage')
            ->and(Setting::get('site_name'))->toBe('My blog')
            ->and(Setting::get('active_theme'))->toBe('howdy')
            ->and(Page::for('home')->user_id)->toBe($admin->id);
    });

    expect(User::count())->toBe(1);
});

test('validates the site', function () {
    Site::create(['domain' => 'example.com', 'path' => 'blog']);

    Livewire::test('pages::dashboard.site')
        ->set('values.path', null)
        ->set('values.admin', [])
        ->call('save')
        ->assertHasErrors([
            'values.domain' => 'required',
            'values.path' => 'required',
            'values.name' => 'required',
            'values.admin.name' => 'required',
            'values.admin.email' => 'required',
            'values.admin.password' => 'required',
        ])
        ->set('values.domain', 'https://example.com')
        ->call('save')
        ->assertHasErrors(['values.domain' => 'regex'])
        ->set('values.domain', 'example.com')
        ->set('values.path', '/blog/')
        ->call('save')
        ->assertHasErrors(['values.domain' => 'unique']);
});

test('edits the domain and path of a site', function () {
    $site = Site::create(['domain' => 'example.com', 'path' => 'blog']);

    Livewire::test('pages::dashboard.site', ['site' => (string) $site->id])
        ->assertSet('values', ['domain' => 'example.com', 'path' => '/blog'])
        ->call('save')
        ->assertHasNoErrors()
        ->set('values.domain', 'blog.example.com')
        ->set('values.path', '/')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'Site updated.');

    expect($site->refresh()->only(['domain', 'path']))->toBe(['domain' => 'blog.example.com', 'path' => '/']);
});
