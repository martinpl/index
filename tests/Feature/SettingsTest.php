<?php

use App\Facades\Filter;
use App\Facades\Settings;
use App\Fields\Repeater;
use App\Fields\Select;
use App\Fields\Text;
use App\Fields\Toggle;
use App\Models\Option;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function settingsManager(): User
{
    return tap(User::factory()->create())->grantAbilities(['manage-settings']);
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

test('the settings page shows the stored options', function () {
    Option::set('site_name', 'Index', 'core');
    actingAs(settingsManager());

    get(route('dashboard.settings', 'general'))
        ->assertOk()
        ->assertSee('wire:model="values.site_name"', false)
        ->assertSeeInOrder([route('dashboard.settings', 'general'), 'General']);

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->assertSet('values', ['site_name' => 'Index']);
});

test('the settings page validates the values', function () {
    actingAs(settingsManager());

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->set('values.site_name', '')
        ->call('save')
        ->assertHasErrors(['values.site_name' => 'The site name field is required.'])
        ->assertSee('The site name field is required.');

    expect(Option::exists('site_name'))->toBeFalse();
});

test('the settings page stores cast values as options owned by the page owner', function () {
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

    expect(Option::get('comments_open'))->toBeTrue()
        ->and(Option::get('order'))->toBe('desc')
        ->and(Option::get('links'))->toBe([['url' => '/about'], ['url' => '/contact']])
        ->and(Option::query()->where('key', 'links')->value('owner'))->toBe('plugins/reading')
        ->and(Option::exists('injected'))->toBeFalse();
});

test('plugins can add fields to a settings page', function () {
    Filter::add('settings.general.fields', fn (array $fields): array => [...$fields, Text::make('tagline')]);
    actingAs(settingsManager());

    Livewire::test('pages::dashboard.settings', ['page' => 'general'])
        ->set('values.site_name', 'Index')
        ->set('values.tagline', 'Just another Index site')
        ->call('save')
        ->assertHasNoErrors();

    expect(Option::get('tagline'))->toBe('Just another Index site');
});

test('registering a duplicate settings page throws', function () {
    Settings::register('general', ['fields' => fn (): array => []], 'plugins/blog');
})->throws(DomainException::class, 'Settings page [general] is already registered by [core].');
