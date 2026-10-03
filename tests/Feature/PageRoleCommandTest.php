<?php

use App\Models\Entry;
use App\Models\Option;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\artisan;

pest()->use(LazilyRefreshDatabase::class);

test('manages page roles', function () {
    $page = Entry::factory()->create(['type' => 'page']);

    artisan('page-role:set', ['role' => 'home', 'page' => $page->id])
        ->expectsOutput("Assigned page [$page->id] to role [home].")
        ->assertSuccessful();

    artisan('page-role:list')
        ->expectsTable(['key', 'name', 'page'], [
            ['home', 'Home page', $page->id],
        ])
        ->assertSuccessful();

    artisan('page-role:set', ['role' => 'home'])
        ->expectsOutput('Unassigned page role [home].')
        ->assertSuccessful();

    expect(Option::get('page_roles'))->toBe([]);

    artisan('page-role:set', ['role' => 'shop', 'page' => $page->id])
        ->expectsOutput('Page role [shop] does not exist.')
        ->assertFailed();
});

test('does not assign a missing page to a role', function () {
    $post = Entry::factory()->create(['type' => 'post']);

    artisan('page-role:set', ['role' => 'home', 'page' => 999])
        ->expectsOutput('Page [999] does not exist.')
        ->assertFailed();

    artisan('page-role:set', ['role' => 'home', 'page' => $post->id])
        ->expectsOutput("Page [$post->id] does not exist.")
        ->assertFailed();

    expect(Option::get('page_roles'))->toBeNull();
});
