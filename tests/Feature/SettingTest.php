<?php

use App\Models\Setting;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('manages settings through commands', function () {
    artisan('setting:set', ['key' => 'site_name', 'value' => 'Index'])
        ->expectsOutput('Set setting [site_name].')
        ->assertSuccessful();

    assertDatabaseHas('settings', ['key' => 'site_name', 'value' => json_encode('Index')]);

    artisan('setting:get', ['key' => 'site_name'])
        ->expectsOutput('Index')
        ->assertSuccessful();

    Setting::set('timezone', 'Europe/Warsaw', 'core');

    artisan('setting:list')
        ->expectsTable(['key', 'value'], [
            ['site_name', 'Index'],
            ['timezone', 'Europe/Warsaw'],
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
