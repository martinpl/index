<?php

use App\Facades\EntryType;
use App\Facades\TermType;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard.home'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard.home'));
    $response->assertOk()
        ->assertSeeText($user->name)
        ->assertSeeText($user->email)
        ->assertSee(route('profile.edit'), false)
        ->assertSee('action="'.route('logout').'"', false);
});

test('the dashboard menu links to every entry type and its term types', function () {
    EntryType::register('post', [], 'core');
    TermType::register('category', ['entry_types' => ['post']], 'core');
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard.entries', 'post'))
        ->assertOk()
        ->assertSeeInOrder(['Pages', route('dashboard.entries', 'page'), 'Posts', route('dashboard.terms', 'category'), 'Categories']);
    $this->get(route('dashboard.terms', 'category'))->assertOk();
    $this->get(route('dashboard.entries', 'missing'))->assertNotFound();
});
