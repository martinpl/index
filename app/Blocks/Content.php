<?php

namespace App\Blocks;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

/**
 * Shows the content of the entry shown in a layout, placing it among the blocks of the layout.
 */
class Content extends Block
{
    public static string $type = 'content';

    /**
     * The contents the layouts being rendered show, the innermost last, each set to null once a content block shows it.
     *
     * @var list<Htmlable|string|null>
     */
    protected static array $contents = [];

    /**
     * @return array{name?: string, icon?: string, description?: string, entry_types?: list<string>}
     */
    public static function config(): array
    {
        return [
            'icon' => 'lucide-square-dashed-text',
            'description' => 'Where the layout shows the content of its entries.',
            'entry_types' => ['layout'],
        ];
    }

    /**
     * Run the callback rendering the blocks of a layout, so the first content block among them shows the given content,
     * returning the HTML it renders and whether a content block showed the content.
     *
     * @param  Closure(): string  $render
     * @return array{0: string, 1: bool}
     */
    public static function showing(Htmlable|string $content, Closure $render): array
    {
        static::$contents[] = $content;

        try {
            $html = $render();

            return [$html, end(static::$contents) === null];
        } finally {
            array_pop(static::$contents);
        }
    }

    /**
     * Render the content of the innermost layout being rendered, or nothing when there is none, when another content block
     * already showed it, or when a content block is among the content itself.
     */
    public function render(): View|string
    {
        $index = array_key_last(static::$contents);
        $content = is_null($index) ? null : static::$contents[$index];

        if (is_null($content)) {
            return '';
        }

        static::$contents[$index] = null;
        $html = $content instanceof Htmlable ? $content->toHtml() : $content;

        if ($html === '') {
            return '';
        }

        return view('blocks.content', [
            'block' => $this,
            'attributes' => $this->htmlAttributes(),
            'content' => new HtmlString($html),
        ]);
    }

    public function placeholder(): string
    {
        return __('The content of each entry shown in the layout goes here.');
    }
}
