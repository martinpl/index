<?php

use App\Entries\Layout;
use App\Facades\Theme;
use App\Models\Entry;
use App\Models\Setting;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    Setting::set('active_theme', 'howdy', 'core');
    Theme::boot();
});

/**
 * A published page at `/about` made of the given blocks, shown in a layout of its content alone.
 *
 * @param  list<array{type: string, data?: array<string, mixed>}>  $blocks
 */
function aboutPage(array $blocks): Entry
{
    Layout::factory()->default()->create(['content' => [['type' => 'content']]]);

    return Entry::factory()->published()->create(['type' => 'page', 'slug' => 'about', 'content' => $blocks]);
}

test('the navbar and the footer show their links, leaving out the ones the site may not link to', function (string $type) {
    aboutPage([['type' => $type, 'data' => ['links' => [
        ['label' => 'About', 'url' => '/about'],
        ['label' => 'Script', 'url' => 'javascript:alert(1)'],
    ]]]]);

    get('/about')
        ->assertOk()
        ->assertSeeInOrder(["class=\"block-$type\"", '<a href="'.url('/about').'" aria-current="page">About</a>'], false)
        ->assertDontSee(['>Script</a>', 'javascript:'], false);
})->with(['navbar', 'footer']);

test('the navbar leads home under the name of the site and the footer shows its copyright, unless given their own', function () {
    travelTo('2030-06-01');
    Setting::set('site_name', 'Acme & Co', 'core');
    $page = aboutPage([['type' => 'navbar'], ['type' => 'footer']]);

    get('/about')->assertOk()->assertSeeInOrder(['<a href="'.url('/').'" rel="home">Acme &amp; Co</a>', '<p>© 2030 Acme &amp; Co</p>'], false);

    $page->update(['content' => [['type' => 'navbar', 'data' => ['title' => 'Acme Docs']], ['type' => 'footer', 'data' => ['text' => "Made by Acme\nin Prague"]]]]);

    get('/about')->assertOk()->assertSeeInOrder(['rel="home">Acme Docs</a>', '<p>Made by Acme<br />', 'in Prague</p>'], false);
});
