<?php

namespace App\Assets;

use Illuminate\Support\Facades\File;

class Css extends Asset
{
    /**
     * The stylesheet inlined and scoped to the given selector, keeping its rules from styling the rest of the page.
     */
    public function scopedTo(string $selector): string
    {
        return "<style>@scope ($selector) {".File::get(public_path($this->publicPath())).'}</style>';
    }

    public function toHtml(): string
    {
        return '<link rel="stylesheet" href="'.e($this->url()).'">';
    }
}
