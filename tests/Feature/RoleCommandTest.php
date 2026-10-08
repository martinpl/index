<?php

use App\Facades\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\artisan;

test('creates, lists and deletes roles', function () {
    artisan('role:create', ['role' => 'shop-manager'])
        ->expectsOutput('Created role [shop-manager].')
        ->assertSuccessful();

    artisan('role:create', ['role' => 'editor', 'name' => 'Chief Editor'])
        ->assertSuccessful();

    artisan('role:create', ['role' => 'editor'])
        ->expectsOutput('Role [editor] already exists.')
        ->assertFailed();

    artisan('role:list')
        ->expectsTable(['key', 'name'], [
            ['editor', 'Chief Editor'],
            ['shop-manager', 'Shop Manager'],
        ])
        ->assertSuccessful();

    artisan('role:delete', ['role' => 'editor'])
        ->expectsOutput('Deleted role [editor].')
        ->assertSuccessful();

    expect(Role::has('editor'))->toBeFalse();

    artisan('role:delete', ['role' => 'editor'])
        ->expectsOutput('Role [editor] does not exist.')
        ->assertFailed();
});

test('gives and revokes roles', function () {
    Role::create('editor');
    $user = User::factory()->create();

    artisan('role:give', ['user' => $user->id, 'role' => 'editor'])
        ->expectsOutput("Gave role [editor] to user [$user->id].")
        ->assertSuccessful();

    expect($user->hasRole('editor'))->toBeTrue();

    artisan('role:give', ['user' => $user->id, 'role' => 'missing'])
        ->expectsOutput('Role [missing] does not exist.')
        ->assertFailed();

    artisan('role:revoke', ['user' => $user->id, 'role' => 'editor'])
        ->expectsOutput("Revoked role [editor] from user [$user->id].")
        ->assertSuccessful();

    expect($user->hasRole('editor'))->toBeFalse();

    artisan('role:revoke', ['user' => $user->id, 'role' => 'editor'])
        ->expectsOutput("User [$user->id] does not have role [editor].")
        ->assertFailed();
});

test('revokes a deleted role from its users', function () {
    Role::create('editor');
    $user = User::factory()->create();
    $user->giveRole('editor');

    artisan('role:delete', ['role' => 'editor'])->assertSuccessful();

    expect($user->hasRole('editor'))->toBeFalse();
});

test('grants, lists and revokes the abilities of a role', function () {
    Role::create('editor');

    artisan('ability:grant', ['abilities' => ['publish-entries', 'edit-entries'], '--role' => 'editor'])
        ->expectsOutput('Granted abilities [publish-entries, edit-entries] to role [editor].')
        ->assertSuccessful();

    artisan('ability:list', ['--role' => 'editor'])
        ->expectsTable(['ability'], [['edit-entries'], ['publish-entries']])
        ->assertSuccessful();

    artisan('ability:revoke', ['abilities' => ['publish-entries'], '--role' => 'editor'])
        ->expectsOutput('Revoked abilities [publish-entries] from role [editor].')
        ->assertSuccessful();

    expect(Role::abilities('editor'))->toBe(['edit-entries']);
});

test('grants abilities to a user directly and through roles', function () {
    Role::create('editor');
    Role::grant('editor', ['edit-entries']);
    $user = User::factory()->create();
    $user->giveRole('editor');

    artisan('ability:grant', ['abilities' => ['manage-options'], '--user' => $user->id])
        ->expectsOutput("Granted abilities [manage-options] to user [$user->id].")
        ->assertSuccessful();

    artisan('ability:list', ['--user' => $user->id])
        ->expectsTable(['ability'], [['edit-entries'], ['manage-options']])
        ->assertSuccessful();

    expect(Gate::forUser($user)->allows('edit-entries'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('manage-options'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete-users'))->toBeFalse();

    artisan('ability:revoke', ['abilities' => ['manage-options'], '--user' => $user->id])
        ->assertSuccessful();

    expect(Gate::forUser($user)->allows('manage-options'))->toBeFalse();
});

test('requires either a role or a user to manage abilities', function (array $options) {
    artisan('ability:list', $options)
        ->expectsOutput('Either the --role or the --user option is required.')
        ->assertFailed();
})->with([
    'neither' => [[]],
    'both' => [['--role' => 'editor', '--user' => 1]],
]);
