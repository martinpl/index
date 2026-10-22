<?php

use App\EntryTypes\Page;
use App\Models\Setting;

/**
 * The URL of the published page assigned to the given role.
 */
function page_url(string $role): ?string
{
    return Page::for($role)?->url();
}

/**
 * Get the value of a setting, or the default if it does not exist.
 */
function setting(string $key, mixed $default = null): mixed
{
    return Setting::get($key, $default);
}
