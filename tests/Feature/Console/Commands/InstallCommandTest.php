<?php

use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('runs migrations and installs a site on an empty database', function () {
    foreach (Schema::getTableListing() as $table) {
        Schema::drop($table);
    }

    artisan('index:install', ['--name' => 'My site'])
        ->expectsQuestion('Site domain', 'example.com')
        ->expectsOutput('Site installed successfully.')
        ->assertSuccessful();

    assertDatabaseHas('sites', ['domain' => 'example.com', 'path' => '/']);
    assertDatabaseCount('sites', 1);
    assertDatabaseCount('options', 3);
    assertDatabaseHas('options', ['key' => 'site_name', 'value' => json_encode('My site')]);
    assertDatabaseHas('options', ['key' => 'active_theme', 'value' => json_encode('index')]);
    assertDatabaseHas('options', ['key' => 'active_plugins', 'value' => json_encode(['blog'])]);
});
