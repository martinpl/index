<?php

use App\Entries\Layout;
use App\Facades\Assets;
use App\Facades\Theme;
use App\Models\Entry;
use App\Models\Setting;

use function Pest\Laravel\get;

beforeEach(function (): void {
    Setting::set('active_theme', 'howdy', 'core');
    Theme::boot();
});

test('loads the theme styles on the site', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'content' => [
        ['type' => 'heading', 'data' => ['text' => 'Welcome']],
    ]]);

    get($entry->url())
        ->assertOk()
        ->assertSee(Assets::get('themes/howdy:theme')->toHtml(), false)
        ->assertSee('<h2 class="block-heading">Welcome</h2>', false);
});

test('adds a default layout with a navbar and a footer around the content, and a blank one with the content alone', function () {
    $page = Entry::factory()->published()->create(['type' => 'page', 'content' => [
        ['type' => 'paragraph', 'data' => ['text' => 'Hello']],
    ]]);

    get($page->url())
        ->assertOk()
        ->assertSeeInOrder(['<header class="block-navbar">', '>Home</a>', '<main class="block-content">', 'Hello', '</main>', '<footer class="block-footer">'], false);

    $page->setMeta('layout', 'blank', 'core');

    expect(Layout::available()->map->name->all())->toBe(['default' => 'Default', 'blank' => 'Blank'])
        ->and(Layout::withAnyStatus()->count())->toBe(0);
    get($page->url())->assertOk()->assertSee('<main class="block-content">', false)->assertDontSee(['block-navbar', 'block-footer']);
});
