<?php

use App\Facades\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Role::create('editor');
    Role::create('author');

    actingAs(tap(User::factory()->create())->grantAbilities(['users.manage']));
});

test('users without the ability cannot add or edit users', function () {
    $user = User::factory()->create();

    actingAs(User::factory()->create());

    get(route('dashboard.users.create'))->assertForbidden();
    get(route('dashboard.users.edit', $user))->assertForbidden();
});

test('returns not found for a missing user', function () {
    get(route('dashboard.users.edit', 999))->assertNotFound();
});

test('adds a verified user with the selected roles', function () {
    Livewire::test('pages::dashboard.user')
        ->set('values.name', 'Jane Doe')
        ->set('values.email', 'jane@example.com')
        ->set('values.password', 'secret-password')
        ->set('values.roles.editor', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'User added.')
        ->assertRedirect(route('dashboard.users.edit', User::firstWhere('email', 'jane@example.com')));

    $user = User::firstWhere('email', 'jane@example.com');

    expect($user->name)->toBe('Jane Doe')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->getRoles())->toBe(['editor']);
});

test('validates the user', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test('pages::dashboard.user')
        ->call('save')
        ->assertHasErrors(['values.name' => 'required', 'values.email' => 'required', 'values.password' => 'required'])
        ->set('values.name', 'Jane Doe')
        ->set('values.email', 'taken@example.com')
        ->set('values.password', 'secret-password')
        ->call('save')
        ->assertHasErrors(['values.email' => 'unique']);
});

test('edits a user, keeping the password when left empty', function () {
    $user = User::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $user->giveRole('editor');
    $password = $user->password;

    Livewire::test('pages::dashboard.user', ['user' => (string) $user->id])
        ->assertSet('values.email', 'jane@example.com')
        ->assertSet('values.roles', ['editor' => true, 'author' => false])
        ->set('values.name', 'Jane Doe')
        ->set('values.roles.editor', false)
        ->set('values.roles.author', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', title: 'User updated.');

    $user->refresh();

    expect($user->name)->toBe('Jane Doe')
        ->and($user->password)->toBe($password)
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->getRoles())->toBe(['author']);
});

test('changes the password and unverifies a changed email address', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    Livewire::test('pages::dashboard.user', ['user' => (string) $user->id])
        ->set('values.email', 'jane.doe@example.com')
        ->set('values.password', 'new-password')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->email)->toBe('jane.doe@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and(Hash::check('new-password', $user->password))->toBeTrue();
});
