<?php

namespace App\Schema;

use App\Rules\SafeUrl;

/**
 * A URL the site links to, like `/about` or `https://example.com`, which may not run scripts, like `javascript:` ones.
 * Before rendering a URL that was not validated by the field, like one stored from the command line,
 * check it with `SafeUrl::allows()`.
 */
class Url extends Text
{
    public function getRules(string $path): array
    {
        return [$path => ['nullable', 'string', 'max:2048', new SafeUrl, ...$this->rules]];
    }
}
