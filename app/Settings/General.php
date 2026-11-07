<?php

namespace App\Settings;

use App\Facades\Role;
use App\Fields\Field;
use App\Fields\Select;
use App\Fields\Text;
use App\Fields\Toggle;
use Closure;
use DateTimeZone;
use Locale;
use ResourceBundle;

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
            Select::make('locale')
                ->label(__('Language'))
                ->options(static::locales(...))
                ->searchable()
                ->default(config('app.locale')),
            Select::make('timezone')
                ->label(__('Timezone'))
                ->description(__('Dates are shown in this timezone.'))
                ->options(fn (): array => array_combine($timezones = DateTimeZone::listIdentifiers(), $timezones))
                ->searchable()
                ->default('UTC'),
            Text::make('date_format')
                ->label(__('Date format'))
                ->view('settings.date-format')
                ->rules('max:255')
                ->default('Y-m-d'),
            Text::make('time_format')
                ->label(__('Time format'))
                ->view('settings.time-format')
                ->rules('max:255')
                ->default('H:i'),
            Select::make('week_starts_on')
                ->label(__('Week starts on'))
                ->options(fn (): array => collect(range(0, 6))
                    ->mapWithKeys(fn (int $day): array => [$day => mb_ucfirst(now()->weekday($day)->dayName)])
                    ->all())
                ->default('1'),
        ];
    }

    /**
     * Every locale known to the system, labeled in its own language. Untranslated ones fall back to the fallback locale.
     *
     * @return array<string, string>
     */
    public static function locales(): array
    {
        return collect(ResourceBundle::getLocales(''))
            ->mapWithKeys(fn (string $locale): array => [$locale => mb_ucfirst(Locale::getDisplayName($locale, $locale))])
            ->sort()
            ->all();
    }

    /**
     * The format dates are shown in.
     */
    public static function dateFormat(): string
    {
        return setting('date_format', 'Y-m-d');
    }

    /**
     * The format times are shown in.
     */
    public static function timeFormat(): string
    {
        return setting('time_format', 'H:i');
    }
}
