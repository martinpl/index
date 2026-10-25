<?php

namespace App\Fields;

use Illuminate\Contracts\View\View;

/**
 * Holds the values of the component's fields, passing the visible fields to its view
 * as `$fields`, ready for the `<x-fields :$fields :$values />` component.
 */
trait WithFields
{
    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * @return list<Field>
     */
    abstract protected function fields(): array;

    /**
     * Fill the values with the given ones cast by the fields, falling back to each field's default.
     *
     * @param  array<string, mixed>  $values
     */
    protected function fillValues(array $values = []): void
    {
        $this->values = $this->form()->cast($values);
    }

    /**
     * Validate the values, returning them cast by the fields, without any value no field holds.
     *
     * @return array<string, mixed>
     */
    protected function validateValues(): array
    {
        $form = $this->form();

        $validated = $this->validate($form->getRules('values'), [], $form->getValidationAttributes('values'))['values'] ?? [];

        return collect($form->getFields())
            ->mapWithKeys(fn (Field $field): array => [$field->name => $field->cast($validated[$field->name] ?? null)])
            ->all();
    }

    public function renderingWithFields(View $view): void
    {
        $view->with('fields', $this->form()->getFields());
    }

    /**
     * The fields grouped under the `values` property.
     */
    protected function form(): Group
    {
        return Group::make('values')->fields($this->fields());
    }
}
