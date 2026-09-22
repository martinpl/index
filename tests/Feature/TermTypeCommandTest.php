<?php

use App\Facades\TermType;

use function Pest\Laravel\artisan;

test('lists registered term types', function () {
    TermType::register('genre', ['entry_types' => ['post', 'page']]);

    artisan('term-type:list')
        ->expectsTable(['key', 'name', 'entry types'], [
            ['category', 'Category', 'post'],
            ['genre', 'Genre', 'post, page'],
            ['tag', 'Tag', 'post'],
        ])
        ->assertSuccessful();
});

test('gets a term type', function () {
    artisan('term-type:get', ['type' => 'category'])
        ->expectsTable(['field', 'value'], [
            ['key', 'category'],
            ['name', 'Category'],
            ['entry types', 'post'],
        ])
        ->assertSuccessful();
});
