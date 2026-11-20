<?php

namespace App\Blocks;

use App\Entries\Section as SectionEntry;
use App\Schema\Select;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

/**
 * Shows the current blocks of the chosen section, so changes to the section show wherever it is shown.
 */
class Section extends Block
{
    public static string $type = 'section';

    /**
     * The IDs of the sections being rendered, the innermost last, keeping a section from rendering inside itself.
     *
     * @var list<int>
     */
    protected static array $rendering = [];

    /**
     * @return array{name?: string, icon?: string, description?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-component', 'description' => 'Blocks kept in sync wherever the section is shown.'];
    }

    public static function schema(): array
    {
        return [
            Select::make('section')
                ->label(__('Section'))
                ->options(fn (): array => SectionEntry::query()->orderBy('name')->pluck('name', 'id')->all())
                ->required()
                ->view('block-editor.section-picker'),
        ];
    }

    public function sectionId(): ?int
    {
        return filled($this->data['section'] ?? null) ? (int) $this->data['section'] : null;
    }

    /**
     * The chosen section, or null when none is chosen or it was trashed or deleted.
     */
    public function section(): ?SectionEntry
    {
        return $this->sectionId() ? SectionEntry::find($this->sectionId()) : null;
    }

    /**
     * Render the blocks of the chosen section, or nothing when they render nothing,
     * or when the section is already being rendered, as when one of its own blocks shows it.
     */
    public function render(): View|string
    {
        $section = $this->section();

        if (! $section || in_array($section->id, static::$rendering, true)) {
            return '';
        }

        static::$rendering[] = $section->id;

        try {
            $content = $section->content->toHtml();
        } finally {
            array_pop(static::$rendering);
        }

        if ($content === '') {
            return '';
        }

        return view('blocks.section', [
            'block' => $this,
            'attributes' => $this->htmlAttributes(),
            'section' => $section,
            'content' => new HtmlString($content),
        ]);
    }

    /**
     * The blocks of the chosen section, which replace the block when it is detached.
     */
    public function detach(): ?BlockCollection
    {
        return $this->section()?->content;
    }
}
