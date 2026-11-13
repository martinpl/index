<?php

use App\EntryTypes\Media;
use App\Facades\Role;
use App\Models\Entry;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(tap(User::factory()->create(['name' => 'Me']))->grantAbilities(['users.manage']));
});

function usersPage(): Testable
{
    return Livewire::test('pages::dashboard.users');
}

test('users without the ability cannot manage users', function () {
    get(route('dashboard.users'))->assertOk();
    get(route('dashboard.home'))->assertSee(route('dashboard.users'));

    actingAs(User::factory()->create());

    get(route('dashboard.users'))->assertForbidden();
    get(route('dashboard.home'))->assertDontSee(route('dashboard.users'));
});

test('lists the users with their roles and published entry count, leaving out their media files', function () {
    Role::create('editor');
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user->giveRole('editor');
    Entry::factory(2)->published()->create(['user_id' => $user->id]);
    Entry::factory()->create(['user_id' => $user->id]);
    Media::factory(3)->create(['user_id' => $user->id]);

    $page = usersPage()
        ->assertSeeInOrder(['Jane Doe', 'jane@example.com', 'Editor'])
        ->assertSeeText('Me — You');

    expect($page->html())->toMatch('/wire:key="listing-cell-'.$user->id.'-entries_count".*?"\s*>\s*2\s*</s');
});

test('escapes the names of the users', function () {
    User::factory()->create(['name' => '<script>alert(1)</script>']);

    usersPage()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('offers a tab for each role with its users', function () {
    Role::create('editor');
    tap(User::factory()->create(['name' => 'Jane Doe']))->giveRole('editor');
    User::factory()->create(['name' => 'John Doe']);

    usersPage()
        ->assertSee(['Jane Doe', 'John Doe'])
        ->set('tab', 'editor')
        ->assertSee('Jane Doe')
        ->assertDontSee('John Doe');
});

test('deletes the selected users except the current one, keeping their entries', function () {
    $users = User::factory(2)->create();
    $entry = Entry::factory()->published()->create(['user_id' => $users[0]->id]);

    usersPage()
        ->set('selected', $users->pluck('id')->push(auth()->id())->map(fn (int $id): string => (string) $id)->all())
        ->call('runAction', 'delete')
        ->assertDispatched('toast', title: '2 users deleted.');

    $users->each(fn (User $user) => assertModelMissing($user));
    assertModelExists(auth()->user());
    assertDatabaseHas('entries', ['id' => $entry->id, 'user_id' => null]);
});
