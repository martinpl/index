<?php

use Illuminate\View\ComponentAttributeBag;

test('component classes give way to conflicting classes of the consumer', function () {
    $attributes = new ComponentAttributeBag(['class' => 'px-4 text-red-500', 'id' => 'card']);

    expect($attributes->twMerge('px-2 py-1 text-sm')->get('class'))->toBe('py-1 text-sm px-4 text-red-500')
        ->and($attributes->get('id'))->toBe('card');
});

test('component classes may be given as several, nested inputs', function () {
    $attributes = new ComponentAttributeBag;

    expect($attributes->twMerge(['block', ['p-2']], 'inline')->get('class'))->toBe('p-2 inline');
});
