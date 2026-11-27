<?php

use App\Models\Entry;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('sets and forgets meta', function () {
    $entry = Entry::factory()->create();

    $entry->setMeta('subtitle', 'Hello', 'core');
    $entry->setMeta('gallery', [1, 2, 3], 'core');

    expect($entry->hasMeta('subtitle'))->toBeTrue()
        ->and($entry->getMeta('subtitle'))->toBe('Hello')
        ->and($entry->getMeta('gallery'))->toBe([1, 2, 3])
        ->and($entry->meta)->toHaveCount(2);

    assertDatabaseHas('entry_meta', [
        'entry_id' => $entry->id,
        'key' => 'subtitle',
        'value' => json_encode('Hello'),
    ]);

    $entry->setMeta('subtitle', 'Updated', 'core');

    expect($entry->getMeta('subtitle'))->toBe('Updated')
        ->and($entry->forgetMeta('subtitle'))->toBe(1)
        ->and($entry->hasMeta('subtitle'))->toBeFalse()
        ->and($entry->getMeta('subtitle', 'fallback'))->toBe('fallback');
});

test('forgets meta set to null or an empty array', function (mixed $value) {
    $entry = Entry::factory()->create();
    $entry->setMeta('gallery', [1, 2, 3], 'core');

    $entry->setMeta('gallery', $value, 'core');

    assertDatabaseMissing('entry_meta', ['entry_id' => $entry->id, 'key' => 'gallery']);
})->with([null, [[]]]);

test('queries models by meta', function () {
    $red = Entry::factory()->create();
    $red->setMeta('color', 'red', 'core');
    $red->setMeta('rating', 5, 'core');
    $blue = Entry::factory()->create();
    $blue->setMeta('color', 'blue', 'core');
    $blue->setMeta('rating', 10, 'core');
    $tagged = Entry::factory()->create();
    $tagged->setMeta('tags', ['laravel', 'index'], 'core');

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
    $entry->setMeta('subtitle', 'Hello', 'core');

    $entry->delete();

    assertDatabaseHas('entry_meta', ['entry_id' => $entry->id, 'key' => 'subtitle']);

    $entry->forceDelete();

    assertDatabaseMissing('entry_meta', ['entry_id' => $entry->id]);
});

test('loads all meta of a model with a single query and reads the changes made through it', function () {
    $entry = Entry::factory()->create();
    $entry->setMeta('views', 10, 'core');
    $entry->setMeta('title', 'Title', 'core');
    $entry = $entry->fresh();

    DB::enableQueryLog();
    $values = [$entry->getMeta('views'), $entry->getMeta('title'), $entry->hasMeta('missing')];
    $queries = count(DB::getQueryLog());

    expect($values)->toBe([10, 'Title', false])
        ->and($queries)->toBe(1);

    $entry->setMeta('views', 11, 'core');
    $entry->forgetMeta('title');

    expect($entry->getMeta('views'))->toBe(11)
        ->and($entry->hasMeta('title'))->toBeFalse();
});

test('reads eager loaded meta without querying', function () {
    $entry = Entry::factory()->create();
    $entry->setMeta('views', 10, 'core');
    $entry->load('meta');

    DB::enableQueryLog();
    $views = $entry->getMeta('views');

    expect($views)->toBe(10)
        ->and(DB::getQueryLog())->toBeEmpty();
});
