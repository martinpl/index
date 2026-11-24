<?php

namespace App\Entries;

use App\Blocks\BlockCollection;
use App\Blocks\Section as SectionBlock;
use App\Enums\EntryStatus;
use App\Facades\Filter;
use App\Models\Entry;
use Closure;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Blocks kept in one place and shown by section blocks, so every entry showing the section shows its current blocks.
 *
 * Sections have the `internal` status, so they are not served as pages nor counted as the entries of their authors,
 * until they are trashed, which hides them wherever they are shown.
 *
 * @property BlockCollection $content
 */
#[UseFactory(SectionFactory::class)]
class Section extends Entry
{
    public static string $type = 'section';

    protected $attributes = [
        'status' => EntryStatus::Internal->value,
        'order' => 0,
    ];

    /**
     * @return array{name?: string, slug?: string, icon?: string, can?: string, listing?: string, editor?: string, menu_position?: int}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-component', 'menu_position' => 30];
    }

    /**
     * Keep the section blocks of a section from showing it inside itself when it is saved in the editor.
     */
    public static function registered(): void
    {
        Filter::add('entry.'.static::$type.'.rules', function (array $rules, ?Section $entry, array $blocks): array {
            if (! $entry) {
                return $rules;
            }

            foreach ($blocks as $index => $block) {
                if ($block['type'] === SectionBlock::$type) {
                    $rules["blocks.$index.data.section"][] = function (string $attribute, mixed $value, Closure $fail) use ($entry): void {
                        if (is_numeric($value) && static::find($value)?->shows($entry)) {
                            $fail(__('The section cannot be shown inside itself.'));
                        }
                    };
                }
            }

            return $rules;
        });
    }

    protected static function booted(): void
    {
        parent::booted();

        // Replaces the scope to published entries, as sections are internal entries.
        static::addGlobalScope('published', function (Builder $query): void {
            $query->where($query->qualifyColumn('status'), EntryStatus::Internal);
        });
    }

    /**
     * Determine if the section is the given one or shows it, through its section blocks or those of the sections they show.
     */
    public function shows(Section $section): bool
    {
        $checked = [];
        $ids = [$this->id];

        while ($ids !== []) {
            if (in_array($section->id, $ids, true)) {
                return true;
            }

            $checked = [...$checked, ...$ids];
            $ids = static::query()
                ->whereKey($ids)
                ->get()
                ->flatMap(fn (Section $shown): array => $shown->content->toBase()
                    ->whereInstanceOf(SectionBlock::class)
                    ->map(fn (SectionBlock $block): ?int => $block->sectionId())
                    ->all())
                ->reject(fn (?int $id): bool => is_null($id) || in_array($id, $checked, true))
                ->unique()
                ->values()
                ->all();
        }

        return false;
    }
}
