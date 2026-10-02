<?php

namespace App\TermTypes;

use App\EntryTypes\Post;
use App\Models\Term;

class Category extends Term
{
    public static string $type = 'category';

    /**
     * @return array{name?: string, entry_types?: list<string>}
     */
    public static function config(): array
    {
        return ['entry_types' => [Post::$type]];
    }
}
