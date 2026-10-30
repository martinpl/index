<?php

namespace App\Assets;

class Js extends Asset
{
    protected bool $module = false;

    /**
     * Load the script as a JavaScript module, letting it `import` others.
     */
    public function module(bool $module = true): static
    {
        $this->module = $module;

        return $this;
    }

    public function toHtml(): string
    {
        $type = $this->module ? ' type="module"' : ' defer';

        return '<script src="'.e($this->url()).'"'.$type.'></script>';
    }
}
