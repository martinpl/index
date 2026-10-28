<?php

namespace App\Blocks;

use App\Fields\Textarea;

class Paragraph extends Block
{
    public static string $type = 'paragraph';

    /**
     * @return array{name?: string, icon?: string, description?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-pilcrow', 'description' => 'Plain text, broken into lines.'];
    }

    public static function fields(): array
    {
        return [
            Textarea::make('text')->label(__('Text'))->rows(3)->rules('string'),
        ];
    }
}
