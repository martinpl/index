<?php

namespace App\Blocks;

use App\Fields\Select;
use App\Fields\Text;

class Heading extends Block
{
    public static string $type = 'heading';

    /**
     * @return array{name?: string, icon?: string, description?: string}
     */
    public static function config(): array
    {
        return ['icon' => 'lucide-heading', 'description' => 'A title introducing a section.'];
    }

    public static function fields(): array
    {
        return [
            Text::make('text')->label(__('Text'))->rules('string', 'max:255'),
            Select::make('level')
                ->label(__('Level'))
                ->options(collect(range(1, 6))->mapWithKeys(fn (int $level): array => ["h$level" => __('Heading :level', ['level' => $level])])->all())
                ->default('h2'),
        ];
    }
}
