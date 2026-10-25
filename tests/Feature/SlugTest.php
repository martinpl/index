<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\Term;

test('generates a unique slug of the type from the name when saved without one', function () {
    Entry::factory()->create(['type' => 'page', 'slug' => 'about-us', 'status' => EntryStatus::Draft]);
    Entry::factory()->create(['type' => 'page', 'slug' => 'about-us-2', 'status' => EntryStatus::Trash]);
    Entry::factory()->create(['type' => 'post', 'slug' => 'about-us-3']);

    $entry = Entry::create(['type' => 'page', 'name' => 'About us', 'owner' => 'core']);
    $post = Entry::create(['type' => 'post', 'name' => 'About us', 'owner' => 'core']);

    expect($entry->slug)->toBe('about-us-3')
        ->and($post->slug)->toBe('about-us')
        ->and(Term::create(['type' => 'tag', 'name' => 'About us', 'owner' => 'core'])->slug)->toBe('about-us');
});

test('keeps a given slug and regenerates a cleared one ignoring the model itself', function () {
    $term = Term::create(['type' => 'category', 'name' => 'News', 'slug' => 'latest', 'owner' => 'core']);

    $term->update(['slug' => null]);

    expect($term->slug)->toBe('news');

    $term->update(['slug' => '', 'name' => 'News']);

    expect($term->slug)->toBe('news');
});
