<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

pest()->use(LazilyRefreshDatabase::class);

test('runs migrations and installs a site on an empty database', function () {
    foreach (Schema::getTableListing() as $table) {
        Schema::drop($table);
    }

    artisan('index:install', ['--name' => 'My site', '--no-interaction' => true])
        ->expectsOutput('Site installed successfully.')
        ->assertSuccessful();

    assertDatabaseCount('sites', 1);
    assertDatabaseCount('options', 1);
    assertDatabaseHas('options', ['key' => 'site_name', 'value' => json_encode('My site')]);
});
