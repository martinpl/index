<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Plugin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Foundation\Plugin::class;
    }
}
