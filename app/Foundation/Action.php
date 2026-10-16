<?php

namespace App\Foundation;

class Action extends HookRegistry
{
    protected string $attribute = \App\Attributes\Action::class;

    public function run(string $hook, mixed ...$payload): void
    {
        foreach ($this->callbacks($hook) as $callback) {
            $callback(...$payload);
        }
    }
}
