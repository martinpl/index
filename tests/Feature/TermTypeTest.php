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
    TermType::register(Genre::class);

    expect(TermType::get('genre'))->toBe([
        'key' => 'genre',
        'name' => 'Genre',
        'entry_types' => ['post'],
        'model' => Genre::class,
    ]);
});

test('uses the key from the class type', function () {
    TermType::register(ShopBrand::class);
    $brand = ShopBrand::create(['slug' => 'acme', 'name' => 'Acme']);

    expect(TermType::get('brand')['model'])->toBe(ShopBrand::class)
        ->and($brand->type)->toBe('brand')
        ->and(ShopBrand::pluck('id')->all())->toBe([$brand->id]);
});

test('requires the class to declare its type', function () {
    expect(fn () => TermType::register(UntypedTerm::class))->toThrow(Error::class);
});

test('hydrates terms as the class of their term type', function () {
    TermType::register(Genre::class);
    $genre = Term::factory()->create(['type' => 'genre']);
    $category = Term::factory()->create(['type' => 'category']);

    expect(Term::find($genre->id))->toBeInstanceOf(Genre::class)
        ->and(Term::find($category->id))->not->toBeInstanceOf(Genre::class);
});

test('keeps meta and entries working for term type classes', function () {
    TermType::register(Genre::class);
    $genre = Genre::create(['slug' => 'jazz', 'name' => 'Jazz']);
    $entry = Entry::factory()->create();

    $genre->setMeta('color', 'blue');
    $genre->entries()->attach($entry);

    assertDatabaseHas('meta', ['metable_type' => 'term', 'metable_id' => $genre->id, 'key' => 'color']);
    assertDatabaseHas('entry_term', ['entry_id' => $entry->id, 'term_id' => $genre->id]);
    expect(Entry::withAnyStatus()->find($entry->id)->terms()->first())->toBeInstanceOf(Genre::class);
});
