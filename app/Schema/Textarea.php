<?php

namespace App\Schema;

class Textarea extends Text
{
    public string $view = 'schema.textarea';

    public int $rows = 4;

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }
}
