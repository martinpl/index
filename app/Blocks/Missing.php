<?php

namespace App\Blocks;

/**
 * A stored block whose type is not registered, like one of a deactivated plugin,
 * kept with its data as it is, but not rendered.
 */
class Missing extends Block
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(protected string $missingType, array $data = [], ?string $id = null, array $attributes = [])
    {
        parent::__construct($data, $id, $attributes);
    }

    public function type(): string
    {
        return $this->missingType;
    }

    public function render(): string
    {
        return '';
    }
}
