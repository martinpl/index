<?php

namespace App\Fields;

class Textarea extends Text
{
    public string $component = 'fields.textarea';

    public int $rows = 4;

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }
}
