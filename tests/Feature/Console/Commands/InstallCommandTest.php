<?php

use App\EntryTypes\Page;
use App\Facades\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    foreach (Schema::getTableListing() as $table) {
        Schema::drop($table);
    }
});

test('runs migrations and installs a site on an empty database', function () {
    artisan('index:install', ['--name' => 'My site', '--admin-password' => 'secret-password'])
        ->expectsQuestion('Site domain', 'example.com')
        ->expectsQuestion('Admin name', 'Taylor')
        ->expectsQuestion('Admin email', 'taylor@example.com')
        ->expectsOutput('Site installed successfully.')
        ->doesntExpectOutputToContain('Admin password')
        ->assertSuccessful();

    assertDatabaseHas('sites', ['domain' => 'example.com', 'path' => '/']);
    assertDatabaseCount('sites', 1);
    assertDatabaseCount('settings', 5);
    assertDatabaseHas('settings', ['key' => 'site_name', 'value' => json_encode('My site')]);
    assertDatabaseHas('settings', ['key' => 'active_theme', 'value' => json_encode('index')]);
    assertDatabaseHas('settings', ['key' => 'active_plugins', 'value' => json_encode(['blog'])]);

    $admin = User::sole();

    expect($admin->email)->toBe('taylor@example.com')
        ->and(Hash::check('secret-password', $admin->password))->toBeTrue()
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and($admin->getRoles())->toBe(['administrator'])
        ->and(Role::list()->keys()->all())->toBe(['administrator'])
        ->and(Role::abilities('administrator'))->toBe(['entries.manage', 'plugins.manage', 'settings.manage'])
        ->and(Page::for('home')?->name)->toBe('Home')
        ->and(Page::for('home')->user_id)->toBe($admin->id)
        ->and(page_url('home'))->toBe(url('/'));
});

test('generates and shows the admin password when none is given', function () {
    artisan('index:install', ['--name' => 'My site', '--domain' => 'example.com', '--admin-name' => 'Taylor', '--admin-email' => 'taylor@example.com'])
        ->expectsOutputToContain('Admin password: ')
        ->assertSuccessful();

    expect(User::sole()->hasRole('administrator'))->toBeTrue();
});
