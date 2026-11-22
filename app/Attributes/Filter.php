<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Filter
{
    /**
     * @param  string|list<string>  $hook  The hook, or a list of hooks, the method is added to.
     */
    public function __construct(public string|array $hook, public int $priority = 10) {}
}
