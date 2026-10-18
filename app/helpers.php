<?php

use App\EntryTypes\Page;
use App\Models\Option;

/**
 * The URL of the published page assigned to the given role.
 */
function page_url(string $role): ?string
{
    return Page::for($role)?->url();
}

/**
 * Get the value of an option, or the default if it does not exist.
 */
function option(string $key, mixed $default = null): mixed
{
    return Option::get($key, $default);
}
