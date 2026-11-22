<?php

namespace Howdy\Blocks;

use App\Blocks\Block;
use App\Schema\Links;
use App\Schema\Textarea;

/**
 * Closes the page with a note, like its copyright, and links, like to its privacy policy.
 */
class Footer extends Block
{
    public static string $type = 'footer';

    /**
     * @return array{name?: string, icon?: string, description?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-panel-bottom', 'description' => 'A note closing the page, like its copyright, with links.'];
    }

    public static function schema(): array
    {
        return [
            Textarea::make('text')
                ->label(__('Text'))
                ->rows(2)
                ->description(__('Defaults to the copyright of the site, like :copyright.', ['copyright' => static::copyright()]))
                ->rules('string', 'max:1000'),
            Links::make('links')->label(__('Links')),
        ];
    }

    /**
     * The copyright of the site this year, like `© 2026 Index`.
     */
    public static function copyright(): string
    {
        return '© '.now()->year.' '.setting('site_name');
    }
}
