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

    public function getRules(string $path): array
    {
        return [$path => ['nullable', Rule::in(array_keys($this->getOptions())), ...$this->rules]];
    }

    public function cast(mixed $value): ?string
    {
        return blank($value) ? null : (string) $value;
    }
}
