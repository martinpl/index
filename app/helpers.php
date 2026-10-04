<?php

use App\EntryTypes\Page;

/**
 * The URL of the published page assigned to the given role.
 */
function page_url(string $role): ?string
{
    return Page::for($role)?->url();
}
