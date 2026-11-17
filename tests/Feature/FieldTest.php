<?php

use App\Schema\Component;
use App\Schema\Group;
use App\Schema\Number;
use App\Schema\Radio;
use App\Schema\Repeater;
use App\Schema\Select;
use App\Schema\Text;
use App\Schema\Toggle;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;

/**
 * @param  list<Component>  $schema
 * @param  array<string, mixed>  $values
 * @return array<string, list<string>>
 */
function validateSchema(array $schema, array $values): array
{
    $form = Group::make('values')->schema($schema);

    return Validator::make(['values' => $values], $form->getRules('values'), [], $form->getValidationAttributes('values'))
        ->errors()
        ->toArray();
}

test('fields validate nested values and name them by their labels', function () {
    $schema = [
        Text::make('title')->label('Page title')->required(),
        Number::make('count')->min(1),
        Select::make('layout')->options(['wide' => 'Wide', 'narrow' => 'Narrow']),
        Group::make('seo')->schema([Text::make('description')->rules('max:5')]),
        Repeater::make('faq')->schema([Text::make('question')->required()]),
    ];

    expect(validateSchema($schema, [
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
    ])->and(validateSchema($schema, ['title' => 'Home', 'count' => '2', 'layout' => 'wide', 'faq' => []]))->toBe([]);
});

test('fields cast values and fill in defaults', function () {
    $form = Group::make('values')->schema([
        Text::make('title'),
        Number::make('count')->default(3),
        Toggle::make('featured'),
        Repeater::make('faq')->schema([Text::make('question'), Toggle::make('open')->default(true)]),
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

test('hidden fields are left out of their parent field', function () {
    $form = Group::make('values')->schema([
        Text::make('title')->required(),
        Text::make('subtitle')->required()->visible(fn (): bool => false),
        Repeater::make('faq')->schema([Text::make('question'), Text::make('answer')->visible(false)]),
    ]);

    expect($form->getDefault())->toBe(['title' => null, 'faq' => []])
        ->and($form->cast(['title' => 'Home', 'subtitle' => 'dropped', 'faq' => [['question' => 'Why?', 'answer' => 'dropped']]]))
        ->toBe(['title' => 'Home', 'faq' => [['question' => 'Why?']]])
        ->and(validateSchema($form->getSchema(), ['title' => 'Home']))->toBe([]);
});

test('layout components hold their fields among the values of their parent', function () {
    $form = Group::make('values')->schema([
        Text::make('title'),
        layout([Text::make('subtitle')->required(), Toggle::make('featured')]),
        layout([Text::make('teaser')])->visible(false),
    ]);

    expect($form->getDefault())->toBe(['title' => null, 'subtitle' => null, 'featured' => false])
        ->and($form->cast(['subtitle' => 'Hello', 'teaser' => 'dropped']))->toBe(['title' => null, 'subtitle' => 'Hello', 'featured' => false])
        ->and(validateSchema($form->getSchema(), []))->toBe(['values.subtitle' => ['The subtitle field is required.']]);
});

test('searchable select fields render a combobox', function () {
    $this->withViewErrors([]);
    $render = fn (Select $field): string => Blade::render('<x-dynamic-component :component="$field->view" :$field path="values.layout" />', ['field' => $field]);
    $field = Select::make('layout')->options(['wide' => 'Wide', 'narrow' => 'Narrow']);

    expect($render($field))->not->toContain('data-slot="combobox"')
        ->and($render($field->searchable()))->toContain('data-slot="combobox"', 'Narrow');
});

test('radio fields render a radio for each option', function () {
    $this->withViewErrors([]);
    $field = Radio::make('layout')->options(['wide' => 'Wide', 'narrow' => 'Narrow']);

    expect(Blade::render('<x-dynamic-component :component="$field->view" :$field path="values.layout" />', ['field' => $field]))
        ->toContain('role="radiogroup"', 'data-value="wide"', 'Wide', 'data-value="narrow"', 'Narrow');
});

test('fields can be rendered with another component', function () {
    expect(Radio::make('format')->view)->toBe('schema.radio')
        ->and(Radio::make('format')->view('settings.date-format')->view)->toBe('settings.date-format');
});
