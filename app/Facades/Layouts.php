<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Layouts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Foundation\Layouts::class;
    }
}
