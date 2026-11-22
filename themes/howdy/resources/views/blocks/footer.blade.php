<footer {{ $attributes }}>
    <p>{!! nl2br(e($text ?: $block::copyright())) !!}</p>
    @if ($links)
        <nav aria-label="{{ __('Footer') }}">
            <ul>
                @foreach ($links as $link)
                    <li><x-link :$link /></li>
                @endforeach
            </ul>
        </nav>
    @endif
</footer>
