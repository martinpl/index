<?php

namespace App\Fields;

use App\Fields\Concerns\HasFields;

class Group extends Field
{
    use HasFields;

    public string $component = 'fields.group';

    public function getDefault(): array
    {
        return [...$this->childDefaults(), ...(value($this->default) ?? [])];
    }

    public function getRules(string $path): array
    {
        return [$path => ['array', ...$this->rules], ...$this->childRules($path)];
    }

    public function getValidationAttributes(string $path): array
    {
        return [...parent::getValidationAttributes($path), ...$this->childValidationAttributes($path)];
    }

    public function cast(mixed $value): array
    {
        return $this->castChildren($value);
    }
}
