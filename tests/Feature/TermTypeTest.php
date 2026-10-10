<?php

use App\Facades\TermType;
use App\Models\Entry;
use App\Models\Term;

use function Pest\Laravel\assertDatabaseHas;

class Genre extends Term
{
    public static string $type = 'genre';

    public static function config(): array
    {
        return ['entry_types' => ['post']];
    }
}

class ShopBrand extends Term
{
    public static string $type = 'brand';
}

class UntypedTerm extends Term {}

test('registers a term type from a class', function () {
    TermType::register(Genre::class, [], 'core');

    expect(TermType::get('genre'))->toBe([
        'key' => 'genre',
        'name' => 'Genre',
        'entry_types' => ['post'],
        'model' => Genre::class,
        'owner' => 'core',
    ]);
});

test('uses the key from the class type', function () {
    TermType::register(ShopBrand::class, [], 'core');
    $brand = ShopBrand::create(['slug' => 'acme', 'name' => 'Acme', 'owner' => 'core']);

    expect(TermType::get('brand')['model'])->toBe(ShopBrand::class)
        ->and($brand->type)->toBe('brand')
        ->and(ShopBrand::pluck('id')->all())->toBe([$brand->id]);
});

test('requires the class to declare its type', function () {
    expect(fn () => TermType::register(UntypedTerm::class, [], 'core'))->toThrow(Error::class);
});

test('hydrates terms as the class of their term type', function () {
    TermType::register(Genre::class, [], 'core');
    $genre = Term::factory()->create(['type' => 'genre']);
    $category = Term::factory()->create(['type' => 'category']);

    expect(Term::find($genre->id))->toBeInstanceOf(Genre::class)
        ->and(Term::find($category->id))->not->toBeInstanceOf(Genre::class);
});

test('keeps meta and entries working for term type classes', function () {
    TermType::register(Genre::class, [], 'core');
    $genre = Genre::create(['slug' => 'jazz', 'name' => 'Jazz', 'owner' => 'core']);
    $entry = Entry::factory()->create();

    $genre->setMeta('color', 'blue', 'core');
    $genre->entries()->attach($entry);

    assertDatabaseHas('meta', ['metable_type' => 'term', 'metable_id' => $genre->id, 'key' => 'color']);
    assertDatabaseHas('entry_term', ['entry_id' => $entry->id, 'term_id' => $genre->id]);
    expect(Entry::withAnyStatus()->find($entry->id)->terms()->first())->toBeInstanceOf(Genre::class);
});
