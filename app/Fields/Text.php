<?php

namespace App\Fields;

class Text extends Field
{
    public string $component = 'fields.text';

    public string $type = 'text';

    public ?string $placeholder = null;

    /**
     * Render the input as another text-like type, such as `email` or `url`.
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function cast(mixed $value): ?string
    {
        return blank($value) ? null : (string) $value;
    }
}
