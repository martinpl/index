<?php

namespace Blog\Terms;

use App\Models\Term;
use Blog\Entries\Post;

class Category extends Term
{
    public static string $type = 'category';

    /**
     * @return array{name?: string, entry_types?: list<string>, hierarchical?: bool}
     */
    public static function config(): array
    {
        return ['entry_types' => [Post::$type], 'hierarchical' => true];
    }
}
