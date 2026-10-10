<?php

namespace App\Foundation;

use App\Models\Term;
use Illuminate\Support\Str;

class TermType extends TypeRegistry
{
    protected string $model = Term::class;

    protected string $label = 'Term type';

    /**
     * @param  array{name?: string, entry_types?: list<string>, model?: class-string<Term>}  $config
     */
    protected function attributes(string $key, array $config): array
    {
        return [
            'name' => $config['name'] ?? Str::headline($key),
            'entry_types' => $config['entry_types'] ?? [],
        ];
    }
}
