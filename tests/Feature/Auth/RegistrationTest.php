<?php

use App\Facades\Role;
use App\Models\Setting;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    Setting::set('users_can_register', true, 'core');
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard.home', absolute: false));

    $this->assertAuthenticated();
});

test('new users are given the default role', function () {
    Role::create('member');
    Setting::set('default_role', 'member', 'core');

    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::firstWhere('email', 'test@example.com')->getRoles())->toBe(['member']);
});

test('new users are given no role when the default role does not exist', function () {
    Setting::set('default_role', 'member', 'core');

    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::firstWhere('email', 'test@example.com')->getRoles())->toBe([]);
});

test('registration is not found when turned off', function () {
    Setting::forget('users_can_register');

    $this->get(route('register'))->assertNotFound();

    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});

test('the login screen links to registration only when it is turned on', function () {
    $this->get(route('login'))->assertSee(route('register'));

    Setting::forget('users_can_register');

    $this->get(route('login'))->assertDontSee(route('register'));
});
