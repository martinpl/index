<?php

use App\Facades\Plugin;
use App\Models\Option;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;

pest()->use(LazilyRefreshDatabase::class);

afterEach(function (): void {
    File::deleteDirectory(base_path('plugins/analytics'));
    File::deleteDirectory(base_path('plugins/cache'));
    File::deleteDirectory(base_path('plugins/seo'));
});

test('manages a plugin through commands', function () {
    $archive = tempnam(sys_get_temp_dir(), 'index-plugin-');
    $zip = new ZipArchive;
    $zip->open($archive, ZipArchive::OVERWRITE);
    $zip->addFromString('seo/composer.json', <<<'JSON'
{
    "name": "index/seo",
    "version": "1.0.0",
    "description": "Search engine optimization."
}
JSON);
    $zip->addFromString('seo/src/Seo.php', <<<'PHP'
<?php

namespace Seo;

class SeoServiceProvider extends \Illuminate\Support\ServiceProvider {}
PHP);
    $zip->close();

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

    File::ensureDirectoryExists(base_path('plugins/cache'));
    File::put(base_path('plugins/cache/composer.json'), <<<'JSON'
{"name":"index/cache"}
JSON);
    File::ensureDirectoryExists(base_path('plugins/cache/src'));
    File::put(base_path('plugins/cache/src/Cache.php'), <<<'PHP'
<?php

namespace Cache;

class CacheServiceProvider extends \Illuminate\Support\ServiceProvider {}
PHP);

    File::delete($archive);
});

test('installs a plugin from an URL', function () {
    $archive = tempnam(sys_get_temp_dir(), 'index-plugin-');
    $zip = new ZipArchive;
    $zip->open($archive, ZipArchive::OVERWRITE);
    $zip->addFromString('analytics/composer.json', <<<'JSON'
{"name":"index/analytics"}
JSON);
    $zip->addFromString('analytics/src/Analytics.php', <<<'PHP'
<?php

namespace Analytics;

class AnalyticsServiceProvider extends \Illuminate\Support\ServiceProvider {}
PHP);
    $zip->close();

    Http::fake(['https://plugins.example.com/analytics.zip' => Http::response(File::get($archive))]);

    artisan('plugin:install', ['source' => 'https://plugins.example.com/analytics.zip'])
        ->expectsOutput('Installed plugin [analytics].')
        ->assertSuccessful();

    expect(base_path('plugins/analytics/composer.json'))->toBeFile();

    File::delete($archive);
});

test('boots an active plugin', function () {
    Option::set('active_plugins', ['hello-index']);

    Plugin::boot();

    $this->get('/hello-index')
        ->assertOk()
        ->assertExactJson(['message' => 'Hello from the Hello Index plugin.']);
});
