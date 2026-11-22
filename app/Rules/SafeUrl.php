<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Allows the URLs the site may link to: a path on the site, an anchor or a query, or a web, email or phone address,
 * keeping out the ones running scripts, like `javascript:` and `data:` ones.
 */
class SafeUrl implements ValidationRule
{
    /**
     * The beginnings of the URLs the site may link to, compared case-insensitively.
     *
     * @var list<string>
     */
    const PREFIXES = ['/', '#', '?', 'http://', 'https://', 'mailto:', 'tel:'];

    /**
     * Determine if the site may link to the given URL, like before rendering a URL that was not validated.
     */
    public static function allows(mixed $url): bool
    {
        return is_string($url) && Str::startsWith(Str::lower($url), self::PREFIXES);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! static::allows($value)) {
            $fail(__('The :attribute field must be a path on the site, like /about, or a web, email or phone address.'));
        }
    }
}
