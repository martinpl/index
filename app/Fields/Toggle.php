<?php

namespace App\Fields;

class Toggle extends Field
{
    public string $component = 'fields.toggle';

    protected mixed $default = false;

    public function getRules(string $path): array
    {
        return [$path => ['boolean', ...$this->rules]];
    }

    public function cast(mixed $value): bool
    {
        return (bool) $value;
    }
}
