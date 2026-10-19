<?php

namespace Blog\EntryTypes;

use App\EntryTypes\Page;
use App\Models\Entry;

class Post extends Entry
{
    public static string $type = 'post';

    /**
     * @return array{name?: string, slug?: string, icon?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-newspaper'];
    }

    public static function registered(): void
    {
        Page::registerRole('posts', 'Posts page');
    }
}
