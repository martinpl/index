{{--
    A link of a `Links` field, given as `link` and showing its label, or one to the given `href`, showing the slot:
    <x-link :$link /> or <x-link href="/" rel="home">Home</x-link>
--}}

@props(['link' => null, 'href' => null])

@php
    use App\Rules\SafeUrl;

    $href ??= $link['url'] ?? null;

    // Paths on the site lead under its address, while anchors, queries and other addresses stay as they are.
    $url = SafeUrl::allows($href) ? (preg_match('#^/(?!/)#', $href) ? url($href) : $href) : null;
@endphp

<a {{ $attributes->merge(['href' => $url, 'aria-current' => $url && $url === url()->current() ? 'page' : null]) }}>{{ $slot->isEmpty() ? $link['label'] ?? '' : $slot }}</a>
