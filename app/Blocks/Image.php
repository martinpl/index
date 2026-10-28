<?php

namespace App\Blocks;

use App\Fields\Text;

class Image extends Block
{
    public static string $type = 'image';

    /**
     * @return array{name?: string, icon?: string, description?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-image', 'description' => 'An image with an optional caption.'];
    }

    public static function fields(): array
    {
        return [
            Text::make('url')->label(__('Image URL'))->type('url')->required()->rules('url', 'max:2048'),
            Text::make('alt')
                ->label(__('Alternative text'))
                ->description(__('Describes the image for people who cannot see it.'))
                ->rules('string', 'max:255'),
            Text::make('caption')->label(__('Caption'))->rules('string', 'max:255'),
        ];
    }
}
