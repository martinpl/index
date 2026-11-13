<?php

namespace App\Blocks;

use App\EntryTypes\Media;
use App\Fields\MediaPicker;
use App\Fields\Text;
use Illuminate\Contracts\View\View;

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
            MediaPicker::make('media')->label(__('Image'))->type('image')->required(),
            Text::make('alt')
                ->label(__('Alternative text'))
                ->description(__('Describes the image for people who cannot see it, defaulting to the one of the chosen image.'))
                ->rules('string', 'max:255'),
            Text::make('caption')->label(__('Caption'))->rules('string', 'max:255'),
        ];
    }

    /**
     * Render the chosen image of the media library, or nothing when it was deleted.
     */
    public function render(): View
    {
        $media = filled($this->data['media'] ?? null) ? Media::ofType('image')->whereKey($this->data['media'])->first() : null;

        return view('blocks.image', [
            'block' => $this,
            'attributes' => $this->htmlAttributes(),
            'media' => $media,
            'alt' => ($this->data['alt'] ?? null) ?: $media?->alt(),
            'caption' => $this->data['caption'] ?? null,
        ]);
    }
}
