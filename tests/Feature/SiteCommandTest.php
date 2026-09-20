<?php

use App\Models\Site;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(LazilyRefreshDatabase::class);

test('manages sites through commands', function () {
    artisan('site:create')
        ->expectsOutput('Created site [1].')
        ->assertSuccessful();

    assertDatabaseHas('sites', ['id' => 1]);

    Site::create();

    artisan('site:list')
        ->expectsTable(['id'], [
            [1],
            [2],
        ])
        ->assertSuccessful();

    artisan('site:delete', ['site' => 1])
        ->expectsOutput('Deleted site [1].')
        ->assertSuccessful();

    assertDatabaseMissing('sites', ['id' => 1]);
});
