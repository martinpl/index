<?php

namespace App\Foundation;

class Action extends HookRegistry
{
    protected string $attribute = \App\Attributes\Action::class;

    /**
     * @var array<string, int>
     */
    protected array $runs = [];

    /**
     * Run the action, or each of a list of them in order, like the one of every entry type and then the one of a single type.
     *
     * @param  string|list<string>  $hook
     */
    public function run(string|array $hook, mixed ...$payload): void
    {
        foreach ((array) $hook as $name) {
            $this->runs[$name] = $this->fired($name) + 1;

            foreach ($this->callbacks($name) as $callback) {
                $callback(...$payload);
            }
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
