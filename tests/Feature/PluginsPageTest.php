<?php

use App\Facades\Plugin;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function pluginManager(): User
{
    return tap(User::factory()->create())->grantAbilities(['plugins.manage']);
}

test('users without the ability cannot manage plugins', function () {
    actingAs(User::factory()->create());

    get(route('dashboard.plugins'))->assertForbidden();
    get(route('dashboard.home'))->assertDontSee(route('dashboard.plugins'));
});

test('lists the installed plugins', function () {
    actingAs(pluginManager());

    get(route('dashboard.home'))->assertSee(route('dashboard.plugins'));
    get(route('dashboard.plugins'))
        ->assertOk()
        ->assertSeeInOrder(['Blog', 'Adds posts with categories and tags to Index.', '1.0.0', 'Hello Index']);
});

test('activates and deactivates plugins', function () {
    actingAs(pluginManager());

    Livewire::test('pages::dashboard.plugins')
        ->set('selected', ['blog', 'hello-index'])
        ->call('runAction', 'activate')
        ->assertDispatched('toast', title: '2 plugins activated.')
        ->set('tab', 'active')
        ->assertSee(['Blog', 'Hello Index'])
        ->call('runAction', 'deactivate', 'blog')
        ->assertDispatched('toast', title: '1 plugin deactivated.');

    expect(Plugin::isActive('blog'))->toBeFalse()
        ->and(Plugin::isActive('hello-index'))->toBeTrue();
});
