<?php

use App\Facades\Filter;
use App\Facades\Settings;
use App\Fields\Repeater;
use App\Fields\Select;
use App\Fields\Text;
use App\Fields\Toggle;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function settingsManager(): User
{
    return tap(User::factory()->create())->grantAbilities(['settings.manage']);
}

test('guests are redirected to the login page', function () {
    get(route('dashboard.settings', 'general'))->assertRedirect(route('login'));
});

test('users without the ability of the page cannot open it', function () {
    actingAs(User::factory()->create());

    get(route('dashboard.settings', 'general'))->assertForbidden();
});

test('unknown settings pages are not found', function () {
    actingAs(settingsManager());

    get(route('dashboard.settings', 'missing'))->assertNotFound();
});

test('the settings page shows the stored settings', function () {
    Setting::set('site_name', 'Index', 'core');
    actingAs(settingsManager());

    get(route('dashboard.settings', 'general'))
        ->assertOk()
        ->assertSee('wire:model="values.site_name"', false)
        ->assertSeeInOrder([route('dashboard.settings', 'general'), 'General']);

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->assertSet('values', ['site_name' => 'Index', 'users_can_register' => false, 'default_role' => null, 'locale' => 'en', 'timezone' => 'UTC', 'date_format' => 'Y-m-d', 'time_format' => 'H:i', 'week_starts_on' => '1']);
});

test('the settings page validates the values', function () {
    actingAs(settingsManager());

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->set('values.site_name', '')
        ->call('save')
        ->assertHasErrors(['values.site_name' => 'The site name field is required.'])
        ->assertSee('The site name field is required.');

    expect(Setting::exists('site_name'))->toBeFalse();
});

test('the settings page stores cast values as settings owned by the page owner', function () {
    Settings::register('reading', [
        'fields' => fn (): array => [
            Toggle::make('comments_open'),
            Select::make('order')->options(['asc' => 'Oldest first', 'desc' => 'Newest first'])->default('desc'),
            Repeater::make('links')->fields([Text::make('url')->required()]),
        ],
    ], 'plugins/reading');
    actingAs(User::factory()->create());

    Livewire::test('pages::dashboard.settings', ['page' => 'reading'])
        ->set('values.comments_open', '1')
        ->set('values.links', [['url' => '/about'], ['url' => '/contact']])
        ->set('values.injected', 'ignored')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('comments_open'))->toBeTrue()
        ->and(Setting::get('order'))->toBe('desc')
        ->and(Setting::get('links'))->toBe([['url' => '/about'], ['url' => '/contact']])
        ->and(Setting::query()->where('key', 'links')->value('owner'))->toBe('plugins/reading')
        ->and(Setting::exists('injected'))->toBeFalse();
});

test('plugins can add fields to a settings page', function () {
    Filter::add('settings.general.fields', fn (array $fields): array => [...$fields, Text::make('tagline')]);
    actingAs(settingsManager());

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->set('values.site_name', 'Index')
        ->set('values.tagline', 'Just another Index site')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('tagline'))->toBe('Just another Index site');
});

test('the settings page leaves out hidden fields, keeping their stored settings', function () {
    Filter::add('settings.general.fields', fn (array $fields): array => [...$fields, Text::make('tagline')->visible(false)]);
    Setting::set('tagline', 'Just another Index site', 'core');
    actingAs(settingsManager());

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->assertDontSee('values.tagline')
        ->set('values.site_name', 'Index')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('tagline'))->toBe('Just another Index site');
});

test('the general settings page stores a custom date format', function () {
    $this->travelTo(now()->setDate(2026, 11, 7));
    actingAs(settingsManager());

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->set('values.site_name', 'Index')
        ->set('values.date_format', 'j F Y')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Preview: 7 November 2026', false)
        ->set('values.date_format', str_repeat('Y', 256))
        ->call('save')
        ->assertHasErrors(['values.date_format' => 'max']);

    expect(Setting::get('date_format'))->toBe('j F Y');
});

test('the general settings page previews only custom date formats', function () {
    $this->travelTo(now()->setDate(2026, 11, 7)->setTime(13, 5));
    actingAs(settingsManager());

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->assertDontSee('Preview:')
        ->set('values.date_format', 'j F Y')
        ->assertSee('Preview: 7 November 2026')
        ->set('values.time_format', 'H:i:s')
        ->assertSee('Preview: 13:05:00');
});

test('registering a duplicate settings page throws', function () {
    Settings::register('general', ['fields' => fn (): array => []], 'plugins/blog');
})->throws(DomainException::class, 'Settings page [general] is already registered by [core].');
