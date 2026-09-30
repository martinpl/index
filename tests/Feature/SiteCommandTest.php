<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(LazilyRefreshDatabase::class);

test('manages sites through commands', function () {
    artisan('site:create', ['domain' => 'example.com'])
        ->expectsOutput('Created site [1].')
        ->assertSuccessful();

    artisan('site:create', ['domain' => 'example.com', '--path' => 'blog'])
        ->expectsOutput('Created site [2].')
        ->assertSuccessful();

    assertDatabaseHas('sites', ['id' => 2, 'domain' => 'example.com', 'path' => '/blog']);
    expect(Schema::hasTable('site_2_entries'))->toBeTrue()
        ->and(Schema::hasTable('site_2_sites'))->toBeFalse();

    artisan('site:create', ['domain' => 'example.com', '--path' => '/blog/'])
        ->expectsOutput('The domain has already been taken.')
        ->assertFailed();

    artisan('site:list')
        ->expectsTable(['id', 'domain', 'path'], [
            [1, 'example.com', '/'],
            [2, 'example.com', '/blog'],
        ])
        ->assertSuccessful();

    artisan('site:delete', ['site' => 1])
        ->expectsOutput('The main site cannot be deleted.')
        ->assertFailed();

    artisan('site:delete', ['site' => 2])
        ->expectsOutput('Deleted site [2].')
        ->assertSuccessful();

    assertDatabaseMissing('sites', ['id' => 2]);
    expect(Schema::hasTable('site_2_entries'))->toBeFalse()
        ->and(Schema::hasTable('site_2_migrations'))->toBeFalse();
});
