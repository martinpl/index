<?php

namespace App\Schema;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

abstract class Field extends Component
{
    protected ?string $label = null;

    protected ?string $description = null;

    protected mixed $default = null;

    /**
     * @var list<string|ValidationRule|Rule>
     */
    protected array $rules = [];

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

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function getDefault(): mixed
    {
        return value($this->default);
    }

    /**
     * @param  string|ValidationRule|Rule|array<int, string|ValidationRule|Rule>  ...$rules
     */
    public function rules(string|ValidationRule|Rule|array ...$rules): static
    {
        $this->rules = [...$this->rules, ...collect($rules)->flatten()->flatMap(
            fn (string|ValidationRule|Rule $rule): array => is_string($rule) ? explode('|', $rule) : [$rule]
        )];

        return $this;
    }

    public function required(): static
    {
        return $this->rules('required');
    }

    public function isRequired(): bool
    {
        return in_array('required', $this->rules, true);
    }

    /**
     * The validation rules of the field and its descendants, keyed by their path.
     *
     * @return array<string, list<string|ValidationRule|Rule>>
     */
    public function getRules(string $path): array
    {
        return [$path => ['nullable', ...$this->rules]];
    }

    /**
     * The validation attribute names of the field and its descendants, keyed by their path.
     *
     * @return array<string, string>
     */
    public function getValidationAttributes(string $path): array
    {
        return [$path => Str::lower($this->getLabel())];
    }

    public function getFields(): array
    {
        return [$this];
    }

    /**
     * Normalize a submitted or stored value into the value the field holds.
     */
    public function cast(mixed $value): mixed
    {
        return $value;
    }

    /**
     * The id of the input rendered for the given path.
     */
    public function id(string $path): string
    {
        return Str::slug(str_replace('.', '-', $path));
    }
}
