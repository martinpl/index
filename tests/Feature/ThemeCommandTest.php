<?php

use App\Facades\Theme;
use App\Models\Option;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Theme\ThemeServiceProvider;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;

pest()->use(LazilyRefreshDatabase::class);

afterEach(function (): void {
    File::deleteDirectory(base_path('themes/classic'));
    File::deleteDirectory(base_path('themes/minimal'));
});

function themeArchive(string $slug): string
{
    $archive = tempnam(sys_get_temp_dir(), 'index-theme-');
    $zip = new ZipArchive;
    $zip->open($archive, ZipArchive::OVERWRITE);
    $zip->addFromString("$slug/composer.json", json_encode(['name' => "index/$slug"]));
    $zip->close();

    return $archive;
}

test('manages a theme through commands', function () {
    $archive = themeArchive('classic');

    artisan('theme:install', ['source' => $archive])
        ->expectsOutput('Installed theme [classic].')
        ->assertSuccessful();

    expect(base_path('themes/classic/composer.json'))->toBeFile();

    artisan('theme:is-installed', ['theme' => 'classic'])
        ->expectsOutput('yes')
        ->assertSuccessful();

    artisan('theme:activate', ['theme' => 'classic'])
        ->expectsOutput('Activated theme [classic].')
        ->assertSuccessful();

    assertDatabaseHas('options', ['key' => 'active_theme', 'value' => json_encode('classic')]);

    artisan('theme:is-active', ['theme' => 'classic'])
        ->expectsOutput('yes')
        ->assertSuccessful();

    artisan('theme:list')
        ->expectsTable(['name', 'status'], [
            ['classic', 'active'],
            ['theme', 'inactive'],
        ])
        ->assertSuccessful();

    artisan('theme:deactivate', ['theme' => 'classic'])
        ->expectsOutput('Deactivated theme [classic].')
        ->assertSuccessful();

    artisan('theme:delete', ['theme' => 'classic'])
        ->expectsOutput('Deleted theme [classic].')
        ->assertSuccessful();

    expect(base_path('themes/classic'))->not->toBeDirectory();

    File::delete($archive);
});

test('installs a theme from an URL', function () {
    $archive = themeArchive('minimal');

    Http::fake(['https://themes.example.com/minimal.zip' => Http::response(File::get($archive))]);

    artisan('theme:install', ['source' => 'https://themes.example.com/minimal.zip'])
        ->expectsOutput('Installed theme [minimal].')
        ->assertSuccessful();

    expect(base_path('themes/minimal/composer.json'))->toBeFile();

    File::delete($archive);
});

test('boots the active theme', function () {
    Option::set('active_theme', 'theme');

    Theme::boot();

    expect(app()->getProviders(ThemeServiceProvider::class))->toHaveCount(1);
});
