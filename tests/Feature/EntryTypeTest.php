<?php

use App\EntryTypes\Page;
use App\Facades\EntryType;
use App\Models\Entry;
use App\Models\Term;

use function Pest\Laravel\assertDatabaseHas;

class Product extends Entry
{
    public static string $type = 'product';

    public static function config(): array
    {
        return ['slug' => 'shop/products'];
    }
}

class ShopEvent extends Entry
{
    public static string $type = 'event';
}

class UntypedEntry extends Entry {}

test('registers an entry type from a class', function () {
    EntryType::register(Product::class, [], 'core');

    expect(EntryType::get('product'))->toBe([
        'key' => 'product',
        'name' => 'Product',
        'slug' => 'shop/products',
        'model' => Product::class,
        'owner' => 'core',
    ]);
});

test('rejects a duplicate key naming its owner', function () {
    EntryType::register('product', [], 'plugins/shop');

    expect(fn () => EntryType::register(Product::class, [], 'core'))
        ->toThrow(DomainException::class, 'Entry type [product] is already registered by [plugins/shop].');
});

test('uses the key from the class type', function () {
    EntryType::register(ShopEvent::class, [], 'core');
    $event = ShopEvent::create(['slug' => 'fair', 'name' => 'Fair', 'status' => 'publish', 'owner' => 'core']);

    expect(EntryType::get('event')['model'])->toBe(ShopEvent::class)
        ->and($event->type)->toBe('event')
        ->and(ShopEvent::pluck('id')->all())->toBe([$event->id]);
});

test('requires the class to declare its type', function () {
    expect(fn () => EntryType::register(UntypedEntry::class, [], 'core'))->toThrow(Error::class);
});

test('hydrates pages as the page class', function () {
    $page = Entry::factory()->published()->create(['type' => 'page']);

    expect(Entry::find($page->id))->toBeInstanceOf(Page::class);
});

test('hydrates entries as the class of their entry type', function () {
    EntryType::register(Product::class, [], 'core');
    $product = Entry::factory()->published()->create(['type' => 'product']);
    $post = Entry::factory()->published()->create(['type' => 'post']);
    $child = Entry::factory()->published()->create(['type' => 'product', 'parent_id' => $product->id]);

    expect(Entry::find($product->id))->toBeInstanceOf(Product::class)
        ->and(Entry::find($post->id))->not->toBeInstanceOf(Product::class)
        ->and(Entry::find($child->id)->parent)->toBeInstanceOf(Product::class);
});

test('queries and creates only entries of its entry type', function () {
    EntryType::register(Product::class, [], 'core');
    Entry::factory()->published()->create(['type' => 'post']);
    $product = Product::create(['slug' => 'chair', 'name' => 'Chair', 'status' => 'publish', 'owner' => 'core']);

    expect($product->type)->toBe('product')
        ->and(Product::pluck('id')->all())->toBe([$product->id])
        ->and($product->path())->toBe('shop/products/chair');
});

test('keeps meta and terms working for entry type classes', function () {
    EntryType::register(Product::class, [], 'core');
    $product = Product::create(['slug' => 'chair', 'name' => 'Chair', 'status' => 'publish', 'owner' => 'core']);
    $term = Term::factory()->create();

    $product->setMeta('price', 10, 'core');
    $product->terms()->attach($term);

    assertDatabaseHas('meta', ['metable_type' => 'entry', 'metable_id' => $product->id, 'key' => 'price']);
    assertDatabaseHas('entry_term', ['entry_id' => $product->id, 'term_id' => $term->id]);
    expect($term->entries()->first())->toBeInstanceOf(Product::class);
});
