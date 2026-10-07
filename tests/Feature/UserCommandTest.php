<?php

use App\Models\Entry;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('creates a user', function () {
    artisan('user:create', ['name' => 'Taylor', 'email' => 'taylor@example.com', '--password' => 'secret-password', '--verified' => true])
        ->expectsOutput('Created user [1].')
        ->doesntExpectOutputToContain('Password:')
        ->assertSuccessful();

    $user = User::find(1);

    expect($user->name)->toBe('Taylor')
        ->and($user->email)->toBe('taylor@example.com')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('creates an unverified user with a generated password', function () {
    artisan('user:create', ['name' => 'Taylor', 'email' => 'taylor@example.com'])
        ->expectsOutput('Created user [1].')
        ->expectsOutputToContain('Password: ')
        ->assertSuccessful();

    expect(User::find(1)->hasVerifiedEmail())->toBeFalse();
});

test('does not create a user with a duplicate email', function () {
    User::factory()->create(['email' => 'taylor@example.com']);

    artisan('user:create', ['name' => 'Taylor', 'email' => 'taylor@example.com'])
        ->expectsOutput('The email has already been taken.')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

test('gets a user', function () {
    $user = User::factory()->create()->fresh();

    artisan('user:get', ['user' => $user->id])
        ->expectsTable(['field', 'value'], [
            ['id', $user->id],
            ['name', $user->name],
            ['email', $user->email],
            ['email_verified_at', $user->email_verified_at],
            ['created_at', $user->created_at],
            ['updated_at', $user->updated_at],
        ])
        ->assertSuccessful();

    artisan('user:get', ['user' => 999])
        ->expectsOutput('User [999] does not exist.')
        ->assertFailed();
});

test('lists users', function () {
    artisan('user:list')
        ->expectsOutput('No users found.')
        ->assertSuccessful();

    [$taylor, $abigail] = User::factory(2)->create()->map->fresh();

    artisan('user:list')
        ->expectsTable(['id', 'name', 'email', 'created_at'], [
            [$taylor->id, $taylor->name, $taylor->email, $taylor->created_at],
            [$abigail->id, $abigail->name, $abigail->email, $abigail->created_at],
        ])
        ->assertSuccessful();
});

test('updates a user', function () {
    $user = User::factory()->create();

    artisan('user:update', ['user' => $user->id, '--name' => 'Taylor', '--email' => 'taylor@example.com', '--password' => 'new-password'])
        ->expectsOutput("Updated user [$user->id].")
        ->assertSuccessful();

    $user->refresh();

    expect($user->name)->toBe('Taylor')
        ->and($user->email)->toBe('taylor@example.com')
        ->and(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

test('deletes a user', function () {
    $user = User::factory()->create();
    $user->setMeta('color', 'red');
    $entry = Entry::factory()->create(['user_id' => $user->id]);

    artisan('user:delete', ['user' => $user->id])
        ->expectsOutput("Deleted user [$user->id].")
        ->assertSuccessful();

    assertDatabaseMissing('users', ['id' => $user->id]);
    assertDatabaseMissing('meta', ['metable_type' => 'user', 'metable_id' => $user->id]);
    assertDatabaseHas('entries', ['id' => $entry->id, 'user_id' => null]);
});

test('reassigns the entries of a deleted user', function () {
    $user = User::factory()->create();
    $heir = User::factory()->create();
    $entry = Entry::factory()->create(['user_id' => $user->id, 'status' => 'trash']);

    artisan('user:delete', ['user' => $user->id, '--reassign' => $heir->id])
        ->assertSuccessful();

    assertDatabaseHas('entries', ['id' => $entry->id, 'user_id' => $heir->id]);
});

test('resets the password of a user', function () {
    Event::fake([PasswordReset::class]);
    Notification::fake();
    $user = User::factory()->create();

    artisan('user:reset-password', ['user' => $user->id, '--show-password' => true])
        ->expectsOutput("Reset password of user [$user->id].")
        ->expectsOutputToContain('Password: ')
        ->expectsOutput("Sent password reset link to user [$user->id].")
        ->assertSuccessful();

    expect(Hash::check('password', $user->refresh()->password))->toBeFalse();

    Event::assertDispatched(PasswordReset::class);
    Notification::assertSentTo($user, ResetPassword::class);
});

test('resets the password of a user without sending the reset link', function () {
    Notification::fake();
    $user = User::factory()->create();

    artisan('user:reset-password', ['user' => $user->id, '--skip-email' => true])
        ->doesntExpectOutputToContain('Password:')
        ->assertSuccessful();

    expect(Hash::check('password', $user->refresh()->password))->toBeFalse();

    Notification::assertNothingSent();
});

test('manages the meta of a user', function () {
    $user = User::factory()->create();

    artisan('user:meta', ['action' => 'set', 'user' => $user->id, 'key' => 'locale', 'value' => 'pl'])
        ->expectsOutput("Set meta [locale] of user [$user->id].")
        ->assertSuccessful();

    artisan('user:meta', ['action' => 'get', 'user' => $user->id, 'key' => 'locale'])
        ->expectsOutput('pl')
        ->assertSuccessful();

    artisan('user:meta', ['action' => 'list', 'user' => $user->id])
        ->expectsTable(['key', 'value'], [
            ['locale', 'pl'],
        ])
        ->assertSuccessful();

    artisan('user:meta', ['action' => 'delete', 'user' => $user->id, 'key' => 'locale'])
        ->expectsOutput("Deleted meta [locale] of user [$user->id].")
        ->assertSuccessful();

    expect($user->hasMeta('locale'))->toBeFalse();
});
