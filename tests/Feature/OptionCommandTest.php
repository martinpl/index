<?php

use App\Models\Option;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(LazilyRefreshDatabase::class);

test('manages options through commands', function () {
    artisan('option:add', ['key' => 'site_name', 'value' => 'Index'])
        ->expectsOutput('Added option [site_name].')
        ->assertSuccessful();

    assertDatabaseHas('options', ['key' => 'site_name', 'value' => 'Index']);

    artisan('option:get', ['key' => 'site_name'])
        ->expectsOutput('Index')
        ->assertSuccessful();

    Option::factory()->create(['key' => 'timezone', 'value' => 'Europe/Warsaw']);

    artisan('option:list')
        ->expectsTable(['key', 'value'], [
            ['site_name', 'Index'],
            ['timezone', 'Europe/Warsaw'],
        ])
        ->assertSuccessful();

    artisan('option:update', ['key' => 'site_name', 'value' => 'Index CMS'])
        ->expectsOutput('Updated option [site_name].')
        ->assertSuccessful();

    assertDatabaseHas('options', ['key' => 'site_name', 'value' => 'Index CMS']);

    artisan('option:delete', ['key' => 'site_name'])
        ->expectsOutput('Deleted option [site_name].')
        ->assertSuccessful();

    assertDatabaseMissing('options', ['key' => 'site_name']);
});
