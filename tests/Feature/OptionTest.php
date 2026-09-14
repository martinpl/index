<?php

use App\Models\Option;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(LazilyRefreshDatabase::class);

test('manages options through commands', function () {
    artisan('option:set', ['key' => 'site_name', 'value' => 'Index'])
        ->expectsOutput('Set option [site_name].')
        ->assertSuccessful();

    assertDatabaseHas('options', ['key' => 'site_name', 'value' => json_encode('Index')]);

    artisan('option:get', ['key' => 'site_name'])
        ->expectsOutput('Index')
        ->assertSuccessful();

    Option::set('timezone', 'Europe/Warsaw');

    artisan('option:list')
        ->expectsTable(['key', 'value'], [
            ['site_name', 'Index'],
            ['timezone', 'Europe/Warsaw'],
        ])
        ->assertSuccessful();

    artisan('option:set', ['key' => 'site_name', 'value' => 'Index CMS'])
        ->expectsOutput('Set option [site_name].')
        ->assertSuccessful();

    assertDatabaseHas('options', ['key' => 'site_name', 'value' => json_encode('Index CMS')]);

    artisan('option:delete', ['key' => 'site_name'])
        ->expectsOutput('Deleted option [site_name].')
        ->assertSuccessful();

    assertDatabaseMissing('options', ['key' => 'site_name']);
});

test('returns a fallback for a missing option', function () {
    expect(Option::get('missing', ['fallback']))->toBe(['fallback']);
});

test('sets and forgets an option', function () {
    Option::set('theme', 'index');

    expect(Option::exists('theme'))->toBeTrue();
    expect(Option::get('theme'))->toBe('index');
    expect(Option::forget('theme'))->toBe(1);
    expect(Option::exists('theme'))->toBeFalse();
    expect(Option::get('theme'))->toBeNull();
});
