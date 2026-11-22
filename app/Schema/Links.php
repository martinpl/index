<?php

namespace App\Schema;

use App\Rules\SafeUrl;

/**
 * Links, like the ones of a navbar, each with its label and the URL it leads to, rendered with `<x-link :$link />`.
 * Their values leave out the links missing either or leading to a URL the site may not link to,
 * like the ones being added in the editor or stored without validation.
 */
class Links extends Repeater
{
    public static function make(string $name): static
    {
        return parent::make($name)->schema([
            Text::make('label')->label(__('Label'))->required()->rules('string', 'max:255'),
            Url::make('url')
                ->label(__('URL'))
                ->description(__('A path on the site, like /about, or a full address, like https://example.com.'))
                ->placeholder('/about')
                ->required(),
        ]);
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function cast(mixed $value): array
    {
        $links = [];

        foreach (parent::cast($value) as $link) {
            if (is_string($link['label']) && is_string($link['url']) && SafeUrl::allows($link['url'])) {
                $links[] = ['label' => $link['label'], 'url' => $link['url']];
            }
        }

        return $links;
    }
}
