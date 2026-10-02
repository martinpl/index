<?php

namespace App\EntryTypes;

use App\Models\Entry;

class Page extends Entry
{
    public static string $type = 'page';

    /**
     * @return array{name?: string, slug?: string}
     */
    public static function config(): array
    {
        return ['slug' => ''];
    }
}
