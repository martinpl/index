<?php

use App\Facades\TermType;

use function Pest\Laravel\artisan;

test('lists registered term types', function () {
    TermType::register('genre', ['entry_types' => ['post', 'page']], 'core');
    TermType::register('mood', [], 'core');

    artisan('term-type:list')
        ->expectsTable(['key', 'name', 'entry types', 'owner'], [
            ['genre', 'Genre', 'post, page', 'core'],
            ['mood', 'Mood', '', 'core'],
        ])
        ->assertSuccessful();
});

test('gets a term type', function () {
    TermType::register('genre', ['entry_types' => ['post']], 'core');

    artisan('term-type:get', ['type' => 'genre'])
        ->expectsTable(['field', 'value'], [
            ['key', 'genre'],
            ['name', 'Genre'],
            ['entry types', 'post'],
            ['owner', 'core'],
        ])
        ->assertSuccessful();
});
