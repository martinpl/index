@if ($url)
    <figure {{ $attributes }}>
        <img src="{{ $url }}" alt="{{ $alt }}" />
        @if ($caption)
            <figcaption>{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
