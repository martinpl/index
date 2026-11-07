<?php

namespace App\Fields;

use Closure;
use Illuminate\Validation\Rule;

class Select extends Field
{
    public string $component = 'fields.select';

    /**
     * @var array<string, string>|Closure(): array<string, string>
     */
    protected array|Closure $options = [];

    protected bool $searchable = false;

    /**
     * @param  array<string, string>|Closure(): array<string, string>  $options  Labels keyed by value.
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
     * Let the options be searched, for long lists.
     */
    public function searchable(bool $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getRules(string $path): array
    {
        return [$path => ['nullable', Rule::in(array_keys($this->getOptions())), ...$this->rules]];
    }

    public function cast(mixed $value): ?string
    {
        return blank($value) ? null : (string) $value;
    }
}
