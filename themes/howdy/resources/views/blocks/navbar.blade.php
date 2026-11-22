<header {{ $attributes }}>
    <x-link href="/" rel="home">{{ $title ?: setting('site_name') }}</x-link>
    @if ($links)
        <nav aria-label="{{ __('Main') }}">
            <ul>
                @foreach ($links as $link)
                    <li><x-link :$link /></li>
                @endforeach
            </ul>
        </nav>
    @endif
</header>
