<?php

use App\Facades\Assets;
use App\Facades\Theme;
use App\Models\Entry;
use App\Models\Setting;

use function Pest\Laravel\get;

beforeEach(function (): void {
    Setting::set('active_theme', 'index', 'core');
    Theme::boot();
});

test('loads the theme styles on the site', function () {
    $entry = Entry::factory()->published()->create(['type' => 'page', 'content' => [
        ['type' => 'heading', 'data' => ['text' => 'Welcome']],
    ]]);

    get($entry->url())
        ->assertOk()
        ->assertSee(Assets::get('themes/index:theme')->toHtml(), false)
        ->assertSee(Assets::get('themes/index:blocks')->toHtml(), false)
        ->assertSee('<h2 >Welcome</h2>', false);
});
