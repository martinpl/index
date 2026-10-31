<?php

use App\Facades\Theme;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    File::ensureDirectoryExists(base_path('themes/classic'));
    File::put(base_path('themes/classic/composer.json'), json_encode([
        'name' => 'index/classic',
        'description' => 'A <b>timeless</b> theme.',
        'version' => '2.1.0',
    ]));

    Theme::activate('howdy');

    actingAs(tap(User::factory()->create())->grantAbilities(['themes.manage']));
});

afterEach(function () {
    File::deleteDirectory(base_path('themes/classic'));
});

test('users without the ability cannot manage themes', function () {
    get(route('dashboard.home'))->assertSee(route('dashboard.themes'));

    actingAs(User::factory()->create());

    get(route('dashboard.themes'))->assertForbidden();
    get(route('dashboard.home'))->assertDontSee(route('dashboard.themes'));
});

test('lists the installed themes, marking the active one', function () {
    get(route('dashboard.themes'))
        ->assertOk()
        ->assertSeeInOrder(['Classic', 'A &lt;b&gt;timeless&lt;/b&gt; theme.', '2.1.0', 'Howdy', '— Active', 'The default theme.', '1.0.0'], false);
});

test('activates a theme, replacing the active one', function () {
    Livewire::test('pages::dashboard.themes')
        ->call('runAction', 'activate', 'classic')
        ->assertDispatched('toast', title: 'Classic activated.')
        ->assertSeeText('Classic — Active')
        ->assertDontSeeText('Howdy — Active');

    expect(Theme::isActive('classic'))->toBeTrue()
        ->and(Theme::isActive('howdy'))->toBeFalse();
});

test('deactivates the active theme, leaving no theme active', function () {
    Livewire::test('pages::dashboard.themes')
        ->call('runAction', 'deactivate', 'howdy')
        ->assertDispatched('toast', title: 'Howdy deactivated.')
        ->assertDontSeeText('— Active');

    expect(Theme::list()->where('status', 'active'))->toBeEmpty();
});

test('does not activate a missing theme', function () {
    Livewire::test('pages::dashboard.themes')
        ->call('runAction', 'activate', 'missing')
        ->assertNotDispatched('toast');

    expect(Theme::isActive('howdy'))->toBeTrue();
});
