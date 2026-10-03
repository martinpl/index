<?php

namespace Blog\EntryTypes;

use App\EntryTypes\Page;
use App\Models\Entry;

class Post extends Entry
{
    public static string $type = 'post';

    public static function registered(): void
    {
        Page::registerRole('posts', 'Posts page');
    }
}
