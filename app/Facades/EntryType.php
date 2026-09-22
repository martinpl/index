<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class EntryType extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Foundation\EntryType::class;
    }
}
