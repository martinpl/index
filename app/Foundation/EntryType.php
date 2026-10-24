<?php

namespace App\Foundation;

use App\Models\Entry;
use Illuminate\Support\Str;

class EntryType extends TypeRegistry
{
    protected string $model = Entry::class;

    protected string $label = 'Entry type';

    /**
     * @param  array{name?: string, slug?: string, icon?: string, can?: string, model?: class-string<Entry>}  $config  The `can` ability allows managing the entries of the type.
     */
    protected function attributes(string $key, array $config): array
    {
        return [
            'name' => $config['name'] ?? Str::headline($key),
            'slug' => $config['slug'] ?? Str::plural($key),
            'icon' => $config['icon'] ?? 'lucide-file-text',
            'can' => $config['can'] ?? 'entries.manage',
        ];
    }
}
