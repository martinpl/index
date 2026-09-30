<?php

use App\Facades\EntryType;

use function Pest\Laravel\artisan;

test('lists registered entry types', function () {
    EntryType::register('product');

    artisan('entry-type:list')
        ->expectsTable(['key', 'name', 'slug'], [
            ['page', 'Page', ''],
            ['post', 'Post', 'posts'],
            ['product', 'Product', 'products'],
        ])
        ->assertSuccessful();
});

test('gets an entry type', function () {
    artisan('entry-type:get', ['type' => 'page'])
        ->expectsTable(['field', 'value'], [
            ['key', 'page'],
            ['name', 'Page'],
            ['slug', ''],
        ])
        ->assertSuccessful();
});
