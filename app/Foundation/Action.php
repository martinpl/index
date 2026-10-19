<?php

namespace App\Foundation;

class Action extends HookRegistry
{
    protected string $attribute = \App\Attributes\Action::class;

    /**
     * @var array<string, int>
     */
    protected array $runs = [];

    public function run(string $hook, mixed ...$payload): void
    {
        $this->runs[$hook] = $this->fired($hook) + 1;

        foreach ($this->callbacks($hook) as $callback) {
            $callback(...$payload);
        }
    }

    /**
     * The number of times the action has run, counting a run that is still in progress.
     */
    public function fired(string $hook): int
    {
        return $this->runs[$hook] ?? 0;
    }
}
