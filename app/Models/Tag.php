<?php

namespace App\Models;

class Tag extends Term
{
    public static string $type = 'tag';

    /**
     * @return array{name?: string, entry_types?: list<string>}
     */
    public static function config(): array
    {
        return ['entry_types' => [Post::$type]];
    }
}
