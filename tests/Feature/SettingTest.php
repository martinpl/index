<?php

use App\Models\Setting;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('manages settings through commands', function () {
    artisan('setting:set', ['key' => 'site_name', 'value' => 'Index', '--autoload' => true])
        ->expectsOutput('Set setting [site_name].')
        ->assertSuccessful();

    assertDatabaseHas('settings', ['key' => 'site_name', 'value' => json_encode('Index')]);

    artisan('setting:get', ['key' => 'site_name'])
        ->expectsOutput('Index')
        ->assertSuccessful();

    artisan('setting:set', ['key' => 'timezone', 'value' => 'Europe/Warsaw'])
        ->expectsOutput('Set setting [timezone].')
        ->assertSuccessful();

    artisan('setting:list')
        ->expectsTable(['key', 'value', 'autoload'], [
            ['site_name', 'Index', 'yes'],
            ['timezone', 'Europe/Warsaw', 'no'],
        ])
        ->assertSuccessful();

    artisan('setting:set', ['key' => 'site_name', 'value' => 'Index CMS'])
        ->expectsOutput('Set setting [site_name].')
        ->assertSuccessful();

    assertDatabaseHas('settings', ['key' => 'site_name', 'value' => json_encode('Index CMS')]);

    artisan('setting:delete', ['key' => 'site_name'])
        ->expectsOutput('Deleted setting [site_name].')
        ->assertSuccessful();

    assertDatabaseMissing('settings', ['key' => 'site_name']);
});

test('returns a fallback for a missing setting', function () {
    expect(Setting::get('missing', ['fallback']))->toBe(['fallback']);
});

test('sets and forgets a setting', function () {
    Setting::set('theme', 'index', 'core');

    expect(Setting::exists('theme'))->toBeTrue();
    expect(Setting::get('theme'))->toBe('index');
    expect(Setting::forget('theme'))->toBe(1);
    expect(Setting::exists('theme'))->toBeFalse();
    expect(Setting::get('theme'))->toBeNull();
});

/**
 * Count the queries the callback runs, as if it ran in a new request.
 */
function settingQueries(Closure $callback): int
{
    app()->forgetScopedInstances();
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    return count(DB::getQueryLog());
}

test('loads every autoloaded setting with a single query', function () {
    Setting::set('site_name', 'Index', 'core', autoload: true);
    Setting::set('timezone', 'Europe/Warsaw', 'core', autoload: true);

    $queries = settingQueries(function () {
        expect(setting('site_name'))->toBe('Index')
            ->and(setting('timezone'))->toBe('Europe/Warsaw');
    });

    expect($queries)->toBe(1);
});

test('loads a setting that is not autoloaded on its own once', function () {
    Setting::set('site_name', 'Index', 'core', autoload: true);
    Setting::set('archive', ['a', 'b'], 'core');

    $queries = settingQueries(function () {
        expect(setting('site_name'))->toBe('Index')
            ->and(setting('archive'))->toBe(['a', 'b'])
            ->and(setting('archive'))->toBe(['a', 'b'])
            ->and(setting('missing'))->toBeNull()
            ->and(setting('missing', 'fallback'))->toBe('fallback');
    });

    expect($queries)->toBe(3);
});

test('reads a changed setting after it was loaded', function () {
    Setting::set('site_name', 'Index', 'core');
    expect(setting('site_name'))->toBe('Index');

    Setting::set('site_name', 'Index CMS', 'core');

    expect(setting('site_name'))->toBe('Index CMS');
});

test('keeps the autoload choice of a setting when its value changes', function () {
    Setting::set('site_name', 'Index', 'core', autoload: true);

    Setting::set('site_name', 'Index CMS', 'core');

    assertDatabaseHas('settings', ['key' => 'site_name', 'autoload' => true]);
});

test('loads the settings of each site from its own table', function () {
    Setting::set('site_name', 'Index', 'core');
    $site = Site::create(['domain' => 'blog.example.com']);
    $site->execute(fn () => Setting::set('site_name', 'Blog', 'core'));
    expect(setting('site_name'))->toBe('Index');

    $name = $site->setting('site_name');

    expect($name)->toBe('Blog')
        ->and(setting('site_name'))->toBe('Index');
});

test('reads a setting of another site without loading its other settings', function () {
    $site = Site::create(['domain' => 'blog.example.com']);
    $site->execute(function () {
        Setting::set('site_name', 'Blog', 'core', autoload: true);
        Setting::set('timezone', 'Europe/Warsaw', 'core', autoload: true);
    });

    $queries = settingQueries(function () use ($site) {
        expect($site->setting('site_name'))->toBe('Blog')
            ->and($site->setting('timezone'))->toBe('Europe/Warsaw')
            ->and($site->setting('missing', 'fallback'))->toBe('fallback');
    });

    expect($queries)->toBe(3);
});

test('loads the autoloaded settings of a site after reading one of them alone', function () {
    $site = Site::create(['domain' => 'blog.example.com']);
    $site->execute(function () {
        Setting::set('site_name', 'Blog', 'core', autoload: true);
        Setting::set('timezone', 'Europe/Warsaw', 'core', autoload: true);
        Setting::set('locale', 'pl', 'core', autoload: true);
    });

    $queries = settingQueries(function () use ($site) {
        expect($site->setting('site_name'))->toBe('Blog')
            ->and($site->execute(fn (): array => [setting('timezone'), setting('locale'), setting('site_name')]))->toBe(['Europe/Warsaw', 'pl', 'Blog']);
    });

    expect($queries)->toBe(2);
});
