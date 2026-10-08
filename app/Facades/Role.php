<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Role extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Foundation\Role::class;
    }
}
