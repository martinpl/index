<?php

namespace Howdy\Blocks;

use App\Blocks\Block;
use App\Schema\Links;
use App\Schema\Text;

/**
 * Heads the page with the title of the site, leading to its home page, and links to its main pages.
 */
class Navbar extends Block
{
    public static string $type = 'navbar';

    /**
     * @return array{name?: string, icon?: string, description?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-panel-top', 'description' => 'The title of the site with links to its main pages.'];
    }

    public static function schema(): array
    {
        return [
            Text::make('title')
                ->label(__('Title'))
                ->description(__('Defaults to the name of the site.'))
                ->rules('string', 'max:255'),
            Links::make('links')->label(__('Links')),
        ];
    }
}
