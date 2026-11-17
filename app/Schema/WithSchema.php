<?php

namespace App\Schema;

use Illuminate\Contracts\View\View;

/**
 * Holds the values of the Livewire component's fields, passing the visible components of its schema to its view
 * as `$schema`, ready for the `<x-schema :$schema :$values />` component.
 */
trait WithSchema
{
    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * @return list<Component>
     */
    abstract protected function schema(): array;

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

        return collect($form->getChildFields())
            ->mapWithKeys(fn (Field $field): array => [$field->name => $field->cast($validated[$field->name] ?? null)])
            ->all();
    }

    public function renderingWithSchema(View $view): void
    {
        $view->with('schema', $this->form()->getSchema());
    }

    /**
     * The schema grouped under the `values` property.
     */
    protected function form(): Group
    {
        return Group::make('values')->schema($this->schema());
    }
}
