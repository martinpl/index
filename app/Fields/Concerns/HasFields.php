<?php

namespace App\Fields\Concerns;

use App\Fields\Field;
use Closure;

trait HasFields
{
    /**
     * @var list<Field>|Closure(): list<Field>
     */
    protected array|Closure $fields = [];

    /**
     * @param  list<Field>|Closure(): list<Field>  $fields
     */
    public function fields(array|Closure $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * The visible child fields.
     *
     * @return list<Field>
     */
    public function getFields(): array
    {
        return array_values(array_filter(value($this->fields), fn (Field $field): bool => $field->isVisible()));
    }

    /**
     * The default values of the child fields, keyed by name.
     *
     * @return array<string, mixed>
     */
    protected function childDefaults(): array
    {
        return collect($this->getFields())
            ->mapWithKeys(fn (Field $field): array => [$field->name => $field->getDefault()])
            ->all();
    }

    /**
     * Cast the given values with the child fields, dropping any value no child field holds.
     *
     * @return array<string, mixed>
     */
    protected function castChildren(mixed $values): array
    {
        $values = is_array($values) ? $values : [];

        return collect($this->getFields())
            ->mapWithKeys(fn (Field $field): array => [$field->name => $field->cast($values[$field->name] ?? $field->getDefault())])
            ->all();
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function childRules(string $path): array
    {
        return collect($this->getFields())
            ->flatMap(fn (Field $field): array => $field->getRules("$path.$field->name"))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function childValidationAttributes(string $path): array
    {
        return collect($this->getFields())
            ->flatMap(fn (Field $field): array => $field->getValidationAttributes("$path.$field->name"))
            ->all();
    }
}
