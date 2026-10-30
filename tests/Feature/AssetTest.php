<?php

use App\Assets\Asset;
use App\Assets\Css;
use App\Assets\Js;
use App\Assets\Vite;
use App\Facades\Assets;
use App\Facades\Filter;
use App\Facades\Theme;
use App\Support\PackageServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    File::ensureDirectoryExists(public_path('themes/shop/css'));
    File::put(public_path('themes/shop/css/shop.css'), 'h1 { color: red; }');
    File::put(public_path('themes/shop/hot'), 'http://localhost:5173');
});

afterEach(function (): void {
    File::deleteDirectory(public_path('themes/shop'));
    File::deleteDirectory(base_path('themes/shop'));
});

test('renders the assets of each context, versioning the files of packages', function () {
    $version = File::lastModified(public_path('themes/shop/css/shop.css'));

    Assets::register(Css::make('shop', 'css/shop.css')->site()->editor(), 'themes/shop');
    Assets::register(Js::make('cart', 'js/cart.js')->module()->dashboard(), 'themes/shop');
    Assets::register(Js::make('admin', 'js/admin.js')->dashboard(), 'core');

    expect(Assets::render('site'))->toBe('<link rel="stylesheet" href="'.asset('themes/shop/css/shop.css')."?v=$version\">")
        ->and(Assets::render('dashboard'))->toBe(implode("\n", [
            '<script src="'.asset('themes/shop/js/cart.js').'" type="module"></script>',
            '<script src="'.asset('js/admin.js').'" defer></script>',
        ]))
        ->and(Assets::render('editor'))->toBe('<style>@scope (.block-preview) {h1 { color: red; }}</style>');
});

test('loads the entry points built by the package\'s own Vite', function () {
    Assets::register(Vite::make('app', ['resources/css/app.css', 'resources/js/app.js']), 'themes/shop');

    expect(Assets::render('site'))
        ->toContain('<script type="module" src="http://localhost:5173/@vite/client"')
        ->toContain('<link rel="stylesheet" href="http://localhost:5173/resources/css/app.css"')
        ->toContain('<script type="module" src="http://localhost:5173/resources/js/app.js"');
});

test('scopes the stylesheets built by the package\'s own Vite to the editor preview', function () {
    Http::fake(['localhost:5173/resources/css/blocks.css?direct' => Http::response('h2 { color: blue; }')]);

    Assets::register(Vite::make('blocks', ['resources/css/blocks.css', 'resources/js/blocks.js'])->editor(), 'themes/shop');

    expect(Assets::render('editor'))
        ->toStartWith('<style>@scope (.block-preview) {h2 { color: blue; }}</style>')
        ->toContain('<script type="module" src="http://localhost:5173/resources/js/blocks.js"')
        ->not->toContain('resources/css/blocks.css');
});

test('packages add assets by their path, telling their type by it', function () {
    (new class(app()) extends PackageServiceProvider
    {
        public function boot(): void
        {
            $this->addAsset('style', 'public/css/style.css')->editor();
            $this->addAsset('menu', 'public/js/menu.js');
            $this->addAsset('cart', 'public/js/cart.mjs');
            $this->addAsset('app', 'resources/js/app.js');
            $this->addAsset('admin', ['resources/css/admin.css', 'resources/js/admin.js'])->dashboard();
        }
    })->boot();

    expect(Assets::list('editor')->map(fn (Asset $asset): string => $asset::class)->all())->toBe(['tests/Feature:style' => Css::class])
        ->and(Assets::list('site')->map(fn (Asset $asset): string => $asset::class)->all())->toBe([
            'tests/Feature:menu' => Js::class,
            'tests/Feature:cart' => Js::class,
            'tests/Feature:app' => Vite::class,
        ])
        ->and(Assets::list('dashboard')->keys()->all())->toBe(['tests/Feature:admin'])
        ->and(Assets::get('tests/Feature:menu')->url())->toBe(asset('tests/Feature/js/menu.js'))
        ->and(Assets::get('tests/Feature:cart')->toHtml())->toContain('type="module"');
});

test('plugins may change the assets through the filter', function () {
    Assets::register(Css::make('shop', 'css/shop.css'), 'themes/shop');
    Assets::register(Css::make('sale', 'css/sale.css'), 'plugins/sale');

    Filter::add('assets', fn (array $assets, string $context): array => collect($assets)->except('themes/shop:shop')->all());

    expect(Assets::list('site')->keys()->all())->toBe(['plugins/sale:sale']);
});

test('registering a duplicate asset of a package throws', function () {
    Assets::register(Css::make('shop', 'css/shop.css'), 'themes/shop');
    Assets::register(Css::make('shop', 'css/other.css'), 'themes/shop');
})->throws(DomainException::class, 'Asset [themes/shop:shop] is already registered.');

test('links the public directory of installed packages, for storage:link too', function () {
    File::deleteDirectory(public_path('themes/shop'));
    File::ensureDirectoryExists(base_path('themes/shop/public'));
    File::put(base_path('themes/shop/composer.json'), json_encode(['name' => 'index/shop']));

    expect(Theme::links())->toHaveKey(public_path('themes/shop'), base_path('themes/shop/public'));

    Theme::link('shop');

    expect(readlink(public_path('themes/shop')))->toBe(base_path('themes/shop/public'));

    Theme::delete('shop');

    expect(is_link(public_path('themes/shop')))->toBeFalse();
});

test('installing a theme links its public directory', function () {
    File::deleteDirectory(public_path('themes/shop'));
    $archive = tempnam(sys_get_temp_dir(), 'index-theme-');
    $zip = new ZipArchive;
    $zip->open($archive, ZipArchive::OVERWRITE);
    $zip->addFromString('shop/composer.json', json_encode(['name' => 'index/shop']));
    $zip->addFromString('shop/public/css/shop.css', 'body {}');
    $zip->close();

    artisan('theme:install', ['source' => $archive])->assertSuccessful();

    expect(public_path('themes/shop/css/shop.css'))->toBeFile()
        ->and(is_link(public_path('themes/shop')))->toBeTrue();

    File::delete(public_path('themes/shop'));
    File::delete($archive);
});
