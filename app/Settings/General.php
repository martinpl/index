<?php

namespace App\Settings;

use App\Facades\Role;
use App\Fields\Field;
use App\Fields\Select;
use App\Fields\Text;
use App\Fields\Toggle;
use Closure;

class General
{
    /**
     * @return array{title: string, page_title: string, can: string, position: int, fields: Closure(): list<Field>}
     */
    public static function config(): array
    {
        return [
            'title' => 'General',
            'page_title' => 'General Settings',
            'can' => 'settings.manage',
            'position' => 0,
            'fields' => static::fields(...),
        ];
    }

    /**
     * @return list<Field>
     */
    public static function fields(): array
    {
        return [
            Text::make('site_name')->label(__('Site name'))->required()->rules('max:255'),
            Toggle::make('users_can_register')->label(__('Anyone can register')),
            Select::make('default_role')
                ->label(__('New user default role'))
                ->options(fn (): array => Role::list()->map(fn (array $role): string => __($role['name']))->all()),
        ];
    }
}
