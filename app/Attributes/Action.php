<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Action
{
    public function __construct(public string $hook, public int $priority = 10) {}
}
