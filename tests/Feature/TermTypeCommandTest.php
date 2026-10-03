<?php

use App\Facades\TermType;

use function Pest\Laravel\artisan;

test('lists registered term types', function () {
    TermType::register('genre', ['entry_types' => ['post', 'page']]);
    TermType::register('mood');

    artisan('term-type:list')
        ->expectsTable(['key', 'name', 'entry types'], [
            ['genre', 'Genre', 'post, page'],
            ['mood', 'Mood', ''],
        ])
        ->assertSuccessful();
});

test('gets a term type', function () {
    TermType::register('genre', ['entry_types' => ['post']]);

    artisan('term-type:get', ['type' => 'genre'])
        ->expectsTable(['field', 'value'], [
            ['key', 'genre'],
            ['name', 'Genre'],
            ['entry types', 'post'],
        ])
        ->assertSuccessful();
});
