<?php

namespace App\Listing;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

/**
 * A predefined subset of the records, offered as a tab with its count, like "Published (2)".
 */
class Tab
{
    use Conditionable, Macroable;

    protected ?string $label = null;

    protected ?Closure $query = null;

    final public function __construct(public string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->name);
    }

    /**
     * @param  Closure  $query  Receives the query and constrains it, or the collection and returns it filtered, to the records of the tab.
     */
    public function query(Closure $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param  Builder<Model>|Collection<array-key, mixed>  $data
     * @return Builder<Model>|Collection<array-key, mixed>
     */
    public function apply(Builder|Collection $data): Builder|Collection
    {
        return $this->query ? ($this->query)($data) ?? $data : $data;
    }
}
