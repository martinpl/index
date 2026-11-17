<?php

namespace App\Schema\Concerns;

use App\Schema\Component;
use App\Schema\Field;
use Closure;

trait HasSchema
{
    /**
     * @var list<Component>|Closure(): list<Component>
     */
    protected array|Closure $schema = [];

    /**
     * @param  list<Component>|Closure(): list<Component>  $schema
     */
    public function schema(array|Closure $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * The visible child components.
     *
     * @return list<Component>
     */
    public function getSchema(): array
    {
        return array_values(array_filter(value($this->schema), fn (Component $component): bool => $component->isVisible()));
    }

    /**
     * The visible fields holding the values of the child components.
     *
     * @return list<Field>
     */
    public function getChildFields(): array
    {
        return array_merge(...array_map(fn (Component $component): array => $component->getFields(), $this->getSchema()));
    }

    /**
     * The default values of the child fields, keyed by name.
     *
     * @return array<string, mixed>
     */
    protected function childDefaults(): array
    {
        return collect($this->getChildFields())
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

        return collect($this->getChildFields())
            ->mapWithKeys(fn (Field $field): array => [$field->name => $field->cast($values[$field->name] ?? $field->getDefault())])
            ->all();
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function childRules(string $path): array
    {
        return collect($this->getChildFields())
            ->flatMap(fn (Field $field): array => $field->getRules("$path.$field->name"))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function childValidationAttributes(string $path): array
    {
        return collect($this->getChildFields())
            ->flatMap(fn (Field $field): array => $field->getValidationAttributes("$path.$field->name"))
            ->all();
    }
}
