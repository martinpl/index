<?php

namespace App\Assets;

class Css extends Asset
{
    public function toHtml(): string
    {
        return '<link rel="stylesheet" href="'.e($this->url()).'">';
    }
}
