<?php

namespace App\Fields;

use App\Fields\Concerns\HasFields;

class Repeater extends Field
{
    use HasFields;

    public string $component = 'fields.repeater';

    protected mixed $default = [];

    /**
     * The values a newly added row starts with.
     *
     * @return array<string, mixed>
     */
    public function getRowDefault(): array
    {
        return $this->childDefaults();
    }

    public function getRules(string $path): array
    {
        return [$path => ['array', ...$this->rules], ...$this->childRules("$path.*")];
    }

    public function getValidationAttributes(string $path): array
    {
        return [...parent::getValidationAttributes($path), ...$this->childValidationAttributes("$path.*")];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cast(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map($this->castChildren(...))
            ->values()
            ->all();
    }
}
