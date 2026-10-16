<?php

namespace App\Foundation;

class Filter extends HookRegistry
{
    protected string $attribute = \App\Attributes\Filter::class;

    public function apply(string $hook, mixed $value, mixed ...$payload): mixed
    {
        foreach ($this->callbacks($hook) as $callback) {
            $value = $callback($value, ...$payload);
        }

        return $value;
    }
}
