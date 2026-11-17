<?php

namespace App\Schema;

class Toggle extends Field
{
    public string $view = 'schema.toggle';

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
