<?php

namespace App\Fields;

class Number extends Field
{
    public string $component = 'fields.number';

    public int|float|null $min = null;

    public int|float|null $max = null;

    public int|float|null $step = null;

    public function min(int|float $min): static
    {
        $this->min = $min;

        return $this->rules("min:$min");
    }

    public function max(int|float $max): static
    {
        $this->max = $max;

        return $this->rules("max:$max");
    }

    public function step(int|float $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function getRules(string $path): array
    {
        return [$path => ['nullable', 'numeric', ...$this->rules]];
    }

    public function cast(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }
}
