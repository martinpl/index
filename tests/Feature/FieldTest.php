<?php

use App\Fields\Field;
use App\Fields\Group;
use App\Fields\Number;
use App\Fields\Repeater;
use App\Fields\Select;
use App\Fields\Text;
use App\Fields\Toggle;
use Illuminate\Support\Facades\Validator;

/**
 * @param  list<Field>  $fields
 * @param  array<string, mixed>  $values
 * @return array<string, list<string>>
 */
function validateFields(array $fields, array $values): array
{
    $form = Group::make('values')->fields($fields);

    return Validator::make(['values' => $values], $form->getRules('values'), [], $form->getValidationAttributes('values'))
        ->errors()
        ->toArray();
}

test('fields validate nested values and name them by their labels', function () {
    $fields = [
        Text::make('title')->label('Page title')->required(),
        Number::make('count')->min(1),
        Select::make('layout')->options(['wide' => 'Wide', 'narrow' => 'Narrow']),
        Group::make('seo')->fields([Text::make('description')->rules('max:5')]),
        Repeater::make('faq')->fields([Text::make('question')->required()]),
    ];

    expect(validateFields($fields, [
        'count' => 0,
        'layout' => 'full',
        'seo' => ['description' => 'Too long'],
        'faq' => [['question' => '']],
    ]))->toBe([
        'values.title' => ['The page title field is required.'],
        'values.count' => ['The count field must be at least 1.'],
        'values.layout' => ['The selected layout is invalid.'],
        'values.seo.description' => ['The description field must not be greater than 5 characters.'],
        'values.faq.0.question' => ['The question field is required.'],
    ])->and(validateFields($fields, ['title' => 'Home', 'count' => '2', 'layout' => 'wide', 'faq' => []]))->toBe([]);
});

test('fields cast values and fill in defaults', function () {
    $form = Group::make('values')->fields([
        Text::make('title'),
        Number::make('count')->default(3),
        Toggle::make('featured'),
        Repeater::make('faq')->fields([Text::make('question'), Toggle::make('open')->default(true)]),
    ]);

    expect($form->getDefault())->toBe(['title' => null, 'count' => 3, 'featured' => false, 'faq' => []])
        ->and($form->cast([
            'title' => '',
            'count' => '2.5',
            'featured' => 1,
            'faq' => [3 => ['question' => 'Why?', 'extra' => 'dropped']],
            'unknown' => 'dropped',
        ]))->toBe([
            'title' => null,
            'count' => 2.5,
            'featured' => true,
            'faq' => [['question' => 'Why?', 'open' => true]],
        ]);
});
