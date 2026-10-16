<?php

namespace App\Foundation;

use App\Attributes\Action as ActionAttribute;
use App\Attributes\Filter as FilterAttribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

abstract class HookRegistry
{
    /**
     * @var array<string, array<int, list<callable|string>>>
     */
    protected array $hooks = [];

    /**
     * @var class-string<ActionAttribute|FilterAttribute>
     */
    protected string $attribute;

    public function __construct(protected Container $container) {}

    /**
     * @param  callable|string  $callback  A callable, or `Class` / `Class@method` resolved from the container.
     */
    public function add(string $hook, callable|string $callback, int $priority = 10): void
    {
        $this->hooks[$hook][$priority][] = $callback;
    }

    /**
     * Register the class's public methods marked with the registry's attribute.
     *
     * @param  class-string  $class
     */
    public function subscribe(string $class): void
    {
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes($this->attribute) as $attribute) {
                $hook = $attribute->newInstance();

                $this->add($hook->hook, "$class@$method->name", $hook->priority);
            }
        }
    }

    public function has(string $hook): bool
    {
        return ! empty($this->hooks[$hook]);
    }

    /**
     * Remove the given callback from the hook, or all of its callbacks.
     */
    public function forget(string $hook, callable|string|null $callback = null): void
    {
        if (is_null($callback)) {
            unset($this->hooks[$hook]);

            return;
        }

        foreach ($this->hooks[$hook] ?? [] as $priority => $callbacks) {
            $this->hooks[$hook][$priority] = array_values(
                array_filter($callbacks, fn (callable|string $registered): bool => $registered !== $callback)
            );
        }

        $this->hooks[$hook] = array_filter($this->hooks[$hook] ?? []);
    }

    /**
     * @return list<callable>
     */
    protected function callbacks(string $hook): array
    {
        $callbacks = $this->hooks[$hook] ?? [];

        ksort($callbacks);

        return array_map($this->resolve(...), array_merge(...$callbacks));
    }

    protected function resolve(callable|string $callback): callable
    {
        if (is_callable($callback)) {
            return $callback;
        }

        [$class, $method] = Str::parseCallback($callback, 'handle');

        $instance = $this->container->make($class);

        return [$instance, method_exists($instance, $method) ? $method : '__invoke'];
    }
}
