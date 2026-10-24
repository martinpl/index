<?php

namespace App\Listing;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

class Column
{
    use Conditionable, Macroable;

    protected ?string $label = null;

    protected bool $sortable = false;

    protected bool $searchable = false;

    protected bool $hideable = true;

    protected bool $hidden = false;

    /**
     * @var array<string, string>|Closure(): array<string, string>
     */
    protected array|Closure $options = [];

    protected bool $filterable = false;

    protected ?Closure $filterUsing = null;

    /**
     * @var (Closure(mixed): (Htmlable|string|null))|null
     */
    protected ?Closure $render = null;

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
     * Allow sorting the records by the database column or attribute of the same name.
     */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * Match the search term against the database column or attribute of the same name.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function hideable(bool $hideable = true): static
    {
        $this->hideable = $hideable;

        return $this;
    }

    public function isHideable(): bool
    {
        return $this->hideable;
    }

    /**
     * Hide the column until the user shows it.
     */
    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param  array<string, string>|Closure(): array<string, string>  $options  Labels keyed by value, shown instead of the value and offered by the filter.
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return value($this->options);
    }

    /**
     * Allow filtering the records by one of the options, matching the database column or attribute of the same name.
     *
     * @param  Closure|null  $using  Receives the query or collection with the selected option, constraining the query or returning the filtered collection.
     */
    public function filterable(?Closure $using = null): static
    {
        $this->filterable = true;
        $this->filterUsing = $using;

        return $this;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * @param  Builder<Model>|Collection<array-key, mixed>  $data
     * @return Builder<Model>|Collection<array-key, mixed>
     */
    public function applyFilter(Builder|Collection $data, string $value): Builder|Collection
    {
        if ($this->filterUsing) {
            return ($this->filterUsing)($data, $value) ?? $data;
        }

        return $data instanceof Builder
            ? $data->where($data->qualifyColumn($this->name), $value)
            : $data->where($this->name, $value);
    }

    /**
     * @param  Closure(mixed): (Htmlable|string|null)  $render
     */
    public function render(Closure $render): static
    {
        $this->render = $render;

        return $this;
    }

    /**
     * The content of the column's cell for the given record.
     */
    public function getContent(mixed $record): Htmlable|string|null
    {
        if ($this->render) {
            return ($this->render)($record);
        }

        $value = data_get($record, $this->name);
        $value = $value instanceof BackedEnum ? $value->value : $value;

        if (is_null($value)) {
            return null;
        }

        return $this->getOptions()[$value] ?? $value;
    }
}
