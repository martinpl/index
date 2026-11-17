<?php

namespace App\Schema;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

abstract class Component
{
    use Conditionable, Macroable;

    /**
     * The Blade component that renders it; a field's receives `field`, `path` and `value` attributes.
     */
    public string $view;

    /**
     * @var bool|Closure(): bool
     */
    protected bool|Closure $visible = true;

    /**
     * Render the component with the given Blade component instead, like `settings.date-format`.
     */
    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Show the component only when the condition is true, leaving it out of the form, validation and saving otherwise.
     *
     * @param  bool|Closure(): bool  $condition
     */
    public function visible(bool|Closure $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function isVisible(): bool
    {
        return (bool) value($this->visible);
    }

    /**
     * The fields holding the component's values among the values of its parent:
     * the field itself, or the visible fields a layout component holds.
     *
     * @return list<Field>
     */
    abstract public function getFields(): array;
}
