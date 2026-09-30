<?php

use App\Models\Entry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(LazilyRefreshDatabase::class);

test('sets and forgets meta', function () {
    $entry = Entry::factory()->create();

    $entry->setMeta('subtitle', 'Hello');
    $entry->setMeta('gallery', [1, 2, 3]);

    expect($entry->hasMeta('subtitle'))->toBeTrue()
        ->and($entry->getMeta('subtitle'))->toBe('Hello')
        ->and($entry->getMeta('gallery'))->toBe([1, 2, 3])
        ->and($entry->meta)->toHaveCount(2);

    assertDatabaseHas('meta', [
        'metable_type' => 'entry',
        'metable_id' => $entry->id,
        'key' => 'subtitle',
        'value' => json_encode('Hello'),
    ]);

    $entry->setMeta('subtitle', 'Updated');

    expect($entry->getMeta('subtitle'))->toBe('Updated')
        ->and($entry->forgetMeta('subtitle'))->toBe(1)
        ->and($entry->hasMeta('subtitle'))->toBeFalse()
        ->and($entry->getMeta('subtitle', 'fallback'))->toBe('fallback');
});

test('keeps meta of trashed entries', function () {
    $entry = Entry::factory()->create();
    $entry->setMeta('subtitle', 'Hello');

    $entry->delete();

    assertDatabaseHas('meta', ['metable_id' => $entry->id, 'key' => 'subtitle']);

    $entry->forceDelete();

    assertDatabaseMissing('meta', ['metable_id' => $entry->id]);
});
