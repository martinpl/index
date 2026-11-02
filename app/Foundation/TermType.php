<?php

namespace App\Foundation;

use App\Facades\Metabox;
use App\Models\Term;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class TermType extends TypeRegistry
{
    protected string $model = Term::class;

    protected string $label = 'Term type';

    /**
     * @param  array{name?: string, entry_types?: list<string>, hierarchical?: bool, can?: string, model?: class-string<Term>}  $config  Terms of a `hierarchical` type may have a parent, like categories. The `can` ability allows managing the terms of the type.
     */
    protected function attributes(string $key, array $config): array
    {
        return [
            'name' => $config['name'] ?? Str::headline($key),
            'entry_types' => $config['entry_types'] ?? [],
            'hierarchical' => $config['hierarchical'] ?? false,
            'can' => $config['can'] ?? 'entries.manage',
        ];
    }

    /**
     * Add a metabox to the editor of the entry types the term type belongs to, for choosing the terms of an entry,
     * listing child terms under their parents.
     *
     * @param  array{key: string, name: string, entry_types: list<string>, owner: string}  $type
     */
    protected function registered(array $type): void
    {
        Metabox::register("terms.{$type['key']}", [
            'render' => function () use ($type): View {
                $terms = Term::where('type', $type['key'])->orderBy('name')->get(['id', 'name', 'parent_id']);
                $children = $terms->groupBy(fn (Term $term): int => $terms->contains('id', $term->parent_id) ? $term->parent_id : 0);

                $flatten = function (int $parent, int $depth) use (&$flatten, $children): array {
                    return $children->get($parent, collect())
                        ->flatMap(fn (Term $term): array => [['term' => $term, 'depth' => $depth], ...$flatten($term->id, $depth + 1)])
                        ->all();
                };

                return view('metaboxes.terms', [
                    'termType' => $type['key'],
                    'name' => Str::plural($type['name']),
                    'terms' => $flatten(0, 0),
                ]);
            },
            'with' => ['title' => Str::plural($type['name'])],
            'location' => array_map(fn (string $entryType): string => "entry.$entryType", $type['entry_types']),
            'position' => 20,
        ], $type['owner']);
    }
}
