<?php

use App\Facades\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Install a plugin whose provider's `bootstrap()` method marks the config with its slug.
 */
function bootstrappingPlugin(string $slug, bool $declared = true): void
{
    $namespace = Str::studly($slug);

    File::ensureDirectoryExists(base_path("plugins/$slug/src"));
    File::put(base_path("plugins/$slug/composer.json"), json_encode([
        'name' => "index/$slug",
        'autoload' => ['psr-4' => ["$namespace\\" => 'src/']],
        'extra' => ['index' => $declared ? ['bootstrap' => true] : []],
    ]));
    File::put(base_path("plugins/$slug/src/{$namespace}ServiceProvider.php"), <<<PHP
        <?php

        namespace $namespace;

        use Illuminate\Support\ServiceProvider;

        class {$namespace}ServiceProvider extends ServiceProvider
        {
            public static function bootstrap(): void
            {
                config(['bootstrapped.$slug' => true]);
            }
        }
        PHP);
}

afterEach(function (): void {
    File::deleteDirectory(base_path('plugins/early-bird'));
    File::deleteDirectory(base_path('plugins/late-bird'));
});

test('calls the bootstrap method of installed plugins declaring it, even when they are not active', function () {
    bootstrappingPlugin('early-bird');

    Plugin::bootstrap();

    expect(Plugin::isActive('early-bird'))->toBeFalse()
        ->and(config('bootstrapped.early-bird'))->toBeTrue();
});

test('does not load plugins that do not declare a bootstrap method', function () {
    bootstrappingPlugin('late-bird', declared: false);

    Plugin::bootstrap();

    expect(config('bootstrapped.late-bird'))->toBeNull()
        ->and(class_exists('LateBird\\LateBirdServiceProvider', false))->toBeFalse();
});

test('does not call the bootstrap method of plugins when they are skipped', function () {
    bootstrappingPlugin('early-bird');
    Plugin::skip();

    Plugin::bootstrap();

    expect(config('bootstrapped.early-bird'))->toBeNull();
});
