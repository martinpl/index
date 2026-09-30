<?php

use App\Models\Entry;
use App\Models\Option;
use App\Models\Site;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\get;

pest()->use(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Site::factory()->create(['domain' => 'localhost']);
    Option::set('active_theme', 'theme');
});

test('returns not found without a home entry', function () {
    get('/')->assertNotFound();
});

test('renders the home entry', function () {
    $entry = Entry::factory()->published()->create(['name' => 'Home']);
    Option::set('home_entry', $entry->id);

    get('/')->assertOk()->assertViewIs('index')->assertSee('Home');
});

test('returns not found when entry is not published', function () {
    $entry = Entry::factory()->create(['status' => 'draft']);
    Option::set('home_entry', $entry->id);

    get('/')->assertNotFound();
});
