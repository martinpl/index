<?php

namespace App\Foundation;

class Filter extends HookRegistry
{
    protected string $attribute = \App\Attributes\Filter::class;

    /**
     * Apply the filter, or each of a list of them in order, like the one of every entry type and then the one of a single type.
     *
     * @param  string|list<string>  $hook
     */
    public function apply(string|array $hook, mixed $value, mixed ...$payload): mixed
    {
        foreach ((array) $hook as $name) {
            foreach ($this->callbacks($name) as $callback) {
                $value = $callback($value, ...$payload);
            }
        }

        return $value;
    }
}
