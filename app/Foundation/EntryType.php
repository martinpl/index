<?php

namespace App\Foundation;

use App\Models\Entry;
use Illuminate\Support\Str;

class EntryType extends TypeRegistry
{
    protected string $model = Entry::class;

    protected string $label = 'Entry type';

    /**
     * The `can` ability allows managing the entries of the type, which the dashboard lists and edits with the
     * `listing` and `editor` Livewire page components, like the media library.
     *
     * @param  array{name?: string, slug?: string, icon?: string, can?: string, listing?: string, editor?: string, model?: class-string<Entry>}  $config
     */
    protected function attributes(string $key, array $config): array
    {
        return [
            'name' => $config['name'] ?? Str::headline($key),
            'slug' => $config['slug'] ?? Str::plural($key),
            'icon' => $config['icon'] ?? 'lucide-file-text',
            'can' => $config['can'] ?? 'entries.manage',
            'listing' => $config['listing'] ?? 'pages::dashboard.entries',
            'editor' => $config['editor'] ?? 'pages::dashboard.entry',
        ];
    }
}
