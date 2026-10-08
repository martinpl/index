<?php

use App\Models\Entry;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

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

test('forgets meta set to null or an empty array', function (mixed $value) {
    $entry = Entry::factory()->create();
    $entry->setMeta('gallery', [1, 2, 3]);

    $entry->setMeta('gallery', $value);

    assertDatabaseMissing('meta', ['metable_id' => $entry->id, 'key' => 'gallery']);
})->with([null, [[]]]);

test('queries models by meta', function () {
    $red = Entry::factory()->create();
    $red->setMeta('color', 'red');
    $red->setMeta('rating', 5);
    $blue = Entry::factory()->create();
    $blue->setMeta('color', 'blue');
    $blue->setMeta('rating', 10);
    $tagged = Entry::factory()->create();
    $tagged->setMeta('tags', ['laravel', 'index']);

    expect(Entry::withAnyStatus()->whereMeta('tags')->pluck('id')->all())->toBe([$tagged->id])
        ->and(Entry::withAnyStatus()->whereMeta('color', 'red')->pluck('id')->all())->toBe([$red->id])
        ->and(Entry::withAnyStatus()->whereMeta('color', '!=', 'red')->pluck('id')->all())->toBe([$blue->id])
        ->and(Entry::withAnyStatus()->whereMeta('color', 'like', 'bl%')->pluck('id')->all())->toBe([$blue->id])
        ->and(Entry::withAnyStatus()->whereMeta('rating', '>', 9)->pluck('id')->all())->toBe([$blue->id])
        ->and(Entry::withAnyStatus()->whereMetaContains('tags', 'index')->pluck('id')->all())->toBe([$tagged->id])
        ->and(Entry::withAnyStatus()->whereMetaContains('tags', 'php')->exists())->toBeFalse();
});

test('keeps meta of trashed entries', function () {
    $entry = Entry::factory()->create();
    $entry->setMeta('subtitle', 'Hello');

    $entry->delete();

    assertDatabaseHas('meta', ['metable_id' => $entry->id, 'key' => 'subtitle']);

    $entry->forceDelete();

    assertDatabaseMissing('meta', ['metable_id' => $entry->id]);
});
