<?php

use App\Facades\Plugin;
use App\Models\Entry;
use App\Models\Option;
use App\Models\Site;
use App\Models\Term;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

afterEach(function (): void {
    File::deleteDirectory(base_path('plugins/analytics'));
    File::deleteDirectory(base_path('plugins/seo'));
});

function pluginArchive(string $slug): string
{
    $archive = tempnam(sys_get_temp_dir(), 'index-plugin-');
    $zip = new ZipArchive;
    $zip->open($archive, ZipArchive::OVERWRITE);
    $zip->addFromString("$slug/composer.json", json_encode(['name' => "index/$slug"]));
    $zip->close();

    return $archive;
}

test('manages a plugin through commands', function () {
    $archive = pluginArchive('seo');

    artisan('plugin:install', ['source' => $archive])
        ->expectsOutput('Installed plugin [seo].')
        ->assertSuccessful();

    expect(base_path('plugins/seo/composer.json'))->toBeFile();

    artisan('plugin:is-installed', ['plugin' => 'seo'])
        ->expectsOutput('yes')
        ->assertSuccessful();

    artisan('plugin:activate', ['plugin' => 'seo'])
        ->expectsOutput('Activated plugin [seo].')
        ->assertSuccessful();

    assertDatabaseHas('options', ['key' => 'active_plugins', 'value' => json_encode(['seo'])]);

    artisan('plugin:is-active', ['plugin' => 'seo'])
        ->expectsOutput('yes')
        ->assertSuccessful();

    artisan('plugin:list')
        ->expectsTable(['name', 'status'], [
            ['blog', 'inactive'],
            ['hello-index', 'inactive'],
            ['seo', 'active'],
        ])
        ->assertSuccessful();

    artisan('plugin:deactivate', ['plugin' => 'seo'])
        ->expectsOutput('Deactivated plugin [seo].')
        ->assertSuccessful();

    artisan('plugin:delete', ['plugin' => 'seo'])
        ->expectsOutput('Deleted plugin [seo].')
        ->assertSuccessful();

    expect(base_path('plugins/seo'))->not->toBeDirectory();

    File::delete($archive);
});

test('installs a plugin from an URL', function () {
    $archive = pluginArchive('analytics');

    Http::fake(['https://plugins.example.com/analytics.zip' => Http::response(File::get($archive))]);

    artisan('plugin:install', ['source' => 'https://plugins.example.com/analytics.zip'])
        ->expectsOutput('Installed plugin [analytics].')
        ->assertSuccessful();

    expect(base_path('plugins/analytics/composer.json'))->toBeFile();

    File::delete($archive);
});

test('deletes a plugin with its data', function () {
    $archive = pluginArchive('seo');
    artisan('plugin:install', ['source' => $archive])->assertSuccessful();
    File::delete($archive);

    $owner = Plugin::owner('seo');
    $owned = Entry::factory()->create(['owner' => $owner]);
    $owned->setMeta('views', 10, 'core');
    $entry = Entry::factory()->create();
    $entry->setMeta('seo_title', 'Title', $owner);
    $entry->setMeta('views', 5, 'core');
    $term = Term::factory()->create(['owner' => $owner]);
    Option::set('seo_settings', ['robots' => true], $owner);
    Option::set('site_name', 'Index', 'core');
    Site::create(['domain' => 'blog.example.com'])->execute(fn () => Option::set('seo_settings', ['robots' => true], $owner));

    artisan('plugin:delete', ['plugin' => 'seo', '--purge' => true])
        ->expectsOutput('Deleted plugin [seo].')
        ->assertSuccessful();

    assertDatabaseMissing('entries', ['id' => $owned->id]);
    assertDatabaseMissing('meta', ['metable_id' => $owned->id]);
    assertDatabaseMissing('meta', ['key' => 'seo_title']);
    assertDatabaseMissing('terms', ['id' => $term->id]);
    assertDatabaseMissing('options', ['key' => 'seo_settings']);
    assertDatabaseMissing('site_2_options', ['key' => 'seo_settings']);
    assertDatabaseHas('entries', ['id' => $entry->id]);
    assertDatabaseHas('meta', ['metable_id' => $entry->id, 'key' => 'views']);
    assertDatabaseHas('options', ['key' => 'site_name']);
});

test('deactivates a deleted plugin on every site', function () {
    $archive = pluginArchive('seo');
    artisan('plugin:install', ['source' => $archive])->assertSuccessful();
    File::delete($archive);

    $site = Site::create(['domain' => 'blog.example.com']);
    $site->execute(fn () => Plugin::activate('seo'));

    artisan('plugin:delete', ['plugin' => 'seo'])->assertSuccessful();

    expect($site->execute(fn () => Option::get('active_plugins')))->toBe([]);
});

test('boots an active plugin', function () {
    Option::set('active_plugins', ['hello-index'], 'core');

    Plugin::boot();

    $this->get('/hello-index')
        ->assertOk()
        ->assertExactJson(['message' => 'Hello from the Hello Index plugin.']);
});
